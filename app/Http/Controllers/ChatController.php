<?php

namespace App\Http\Controllers;

use App\Models\ResourceFile;
use App\Models\Subject;
use App\Services\AmiraChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Halaman chat AMIRA untuk siswa.
     */
    public function index()
    {
        $providersConfig = config('amira.providers');
        $defaultProvider = config('amira.default_provider');
        $defaultModels   = config('amira.default_models', []);

        $defaultModel = $defaultModels[$defaultProvider] ?? null;

        $subjects  = Subject::orderBy('name')->get();
        $resources = ResourceFile::with('subjects')->latest()->get();

        return view('amira.chat', [
            'providersConfig' => $providersConfig,
            'defaultProvider' => $defaultProvider,
            'defaultModel'    => $defaultModel,
            'subjects'        => $subjects,
            'resources'       => $resources,
        ]);
    }

    /**
     * Endpoint AJAX untuk mengirim pesan + file/gambar ke AMIRA.
     */
    public function chat(Request $request, AmiraChatService $amiraChatService)
    {
        $validated = $request->validate([
            'message'     => 'nullable|string',
            'provider'    => 'nullable|in:gemini,groq',
            'model'       => 'nullable|string',
            'resources'   => 'nullable|array',
            'resources.*' => 'integer|exists:resource_files,id',
            'image'       => 'nullable|image|max:15360', // 15MB
            'document'    => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt|max:51200', // 50MB
        ]);

        $hasMessage  = trim($validated['message'] ?? '') !== '';
        $hasImage    = $request->hasFile('image');
        $hasDocument = $request->hasFile('document');

        if (! $hasMessage && ! $hasImage && ! $hasDocument) {
            return response()->json([
                'success' => false,
                'error'   => 'Minimal isi pesan, unggah gambar, atau unggah dokumen.',
            ], 422);
        }

        $provider = $validated['provider'] ?? config('amira.default_provider');

        // ====== 1) BACA RESOURCE TERPILIH (DARI DATABASE) ======
        $contextText = null;

        $resourceIds = $validated['resources'] ?? [];
        if (!empty($resourceIds)) {
            $resources = ResourceFile::with('subjects')
                ->whereIn('id', $resourceIds)
                ->get();

            if ($resources->isNotEmpty()) {
                $lines = [];
                foreach ($resources as $index => $res) {
                    $subjects = $res->subjects->pluck('name')->implode(', ');
                    $desc     = $res->description ? Str::limit($res->description, 200) : null;

                    $line = ($index + 1) . '. ' . $res->title;
                    if ($subjects) {
                        $line .= " (Mapel: {$subjects})";
                    }
                    if ($desc) {
                        $line .= " – Ringkasan: {$desc}";
                    }

                    $lines[] = $line;
                }

                $contextText = "Bahan ajar yang dirujuk siswa:\n" . implode("\n", $lines);
            }
        }

        // ====== 2) DOKUMEN BARU DI-UPLOAD (SEMENTARA) -> Gemini File API ======
        $geminiFileData = null;

        if ($hasDocument) {
            $file      = $request->file('document');
            $mime      = $file->getClientMimeType();
            $extension = strtolower($file->getClientOriginalExtension());

            $extra = "Siswa mengunggah dokumen bernama '{$file->getClientOriginalName()}' (tipe: {$mime}).";

            // Kalau provider = Gemini → coba upload ke File API
            if ($provider === 'gemini') {
                $uploaded = $amiraChatService->uploadGeminiFile($file);

                if ($uploaded) {
                    $geminiFileData = [
                        'uri'      => $uploaded['uri'],
                        'mimeType' => $uploaded['mimeType'],
                    ];

                    $extra .= "\nGunakan isi dokumen ini sebagai bahan utama untuk menjawab pertanyaan atau memberi ringkasan yang membantu siswa.";
                } else {
                    // Fallback: kalau simple text file, ekstrak sebagian teks
                    $docText = null;

                    if (in_array($extension, ['txt', 'md', 'csv'])) {
                        $docText = file_get_contents($file->getRealPath());
                        $docText = Str::limit($docText, 8000);
                    }

                    if ($docText) {
                        $extra .= "\nBerikut cuplikan isi dokumen:\n" . $docText;
                    } else {
                        $extra .= "\nJika isi detail dokumen tidak terbaca, berikan jawaban umum dan ajak siswa menuliskan poin penting dari dokumen tersebut.";
                    }
                }
            } else {
                // Provider bukan Gemini → hanya pakai teks sederhana sebagai konteks
                $docText = null;
                if (in_array($extension, ['txt', 'md', 'csv'])) {
                    $docText = file_get_contents($file->getRealPath());
                    $docText = Str::limit($docText, 8000);
                }

                if ($docText) {
                    $extra .= "\nBerikut cuplikan isi dokumen:\n" . $docText;
                } else {
                    $extra .= "\nGunakan informasi ini hanya sebagai catatan bahwa siswa sedang merujuk ke dokumen tersebut.";
                }
            }

            $contextText = trim(($contextText ? $contextText . "\n\n" : '') . $extra);
        }

        // ====== 3) BACA GAMBAR YANG DI-UPLOAD ======
        $imageBase64   = null;
        $imageMimeType = null;

        if ($hasImage) {
            $image         = $request->file('image');
            $imageMimeType = $image->getClientMimeType();
            $imageBase64   = base64_encode(file_get_contents($image->getRealPath()));
        }

        // ====== 4) PANGGIL SERVICE AMIRA ======
        $reply = $amiraChatService->chat(
            $validated['message'] ?? '',
            $provider,
            $validated['model'] ?? null,
            $contextText,
            $imageBase64,
            $imageMimeType,
            $geminiFileData
        );

        return response()->json([
            'success' => true,
            'reply'   => $reply,
        ]);
    }
}
