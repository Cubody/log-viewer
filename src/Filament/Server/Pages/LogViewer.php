<?php

namespace Rutale\LogViewer\Filament\Server\Pages;

use App\Repositories\Daemon\DaemonFileRepository;
use App\Enums\TablerIcon;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class LogViewer extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = TablerIcon::FileText;
    protected static ?string $navigationLabel = 'Log Viewer';
    protected static ?string $title = 'Log Viewer';
    protected static ?string $slug = 'log-viewer';
    protected static ?int $navigationSort = 50;
    public function getMaxContentWidth(): ?string { return "full"; }

    protected string $view = 'log-viewer::log-viewer';

    public ?string $selectedFile = null;
    public ?string $dateFilter = null;
    public array $logFiles = [];
    public array $filteredFiles = [];

    public function mount(): void
    {
        try {
            $this->loadLogFiles();
            if (!empty($this->filteredFiles)) {
                $this->selectedFile = $this->filteredFiles[0]['path'];
            }
        } catch (\Exception $e) {
            Log::error('LogViewer: mount failed', ['error' => $e->getMessage()]);
        }
    }

    public function form(Schema $form): Schema
    {
        return $form->components([
            DatePicker::make('dateFilter')
                ->label('Filter by date')
                ->native(false)
                ->displayFormat('d.m.Y')
                ->reactive()
                ->afterStateUpdated(fn () => $this->filterFiles()),
            Select::make('selectedFile')
                ->label('Log file')
                ->options(fn () => $this->getFileOptions())
                ->reactive()
                ->searchable()
                ->placeholder('Select a log file...'),
        ]);
    }

    /**
     * Returns lines from $fromLine to end. If $fromLine=0, returns all.
     * If file rotated (fewer lines than $fromLine), returns reset signal.
     */
    public function pollNewLines(int $fromLine): array
    {
        if (empty($this->selectedFile)) return [];

        try {
            $server = filament()->getTenant();
            $fileRepository = app(DaemonFileRepository::class)->setServer($server);
            $content = $fileRepository->getContent($this->selectedFile);

            if (strlen($content) > 5 * 1024 * 1024) {
                $content = substr($content, -5 * 1024 * 1024);
            }

            $lines = explode("\n", $content);
            $total = count($lines);

            // File rotated/truncated
            if ($fromLine > 0 && $total < $fromLine) {
                return ['reset' => true, 'lines' => $this->buildLines($lines, 0)];
            }

            // No new lines
            if ($total <= $fromLine) return [];

            return $this->buildLines($lines, $fromLine);
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function buildLines(array $allLines, int $fromIndex): array
    {
        // Detect levels for ALL lines (stack trace inheritance needs context)
        $levels = $this->detectLevels($allLines);

        $result = [];
        for ($i = $fromIndex; $i < count($allLines); $i++) {
            $result[] = [
                'number' => $i + 1,
                'text' => $allLines[$i],
                'level' => $levels[$i],
            ];
        }
        return $result;
    }

    protected function detectLevels(array $lines): array
    {
        $levels = [];
        $lastLevel = 'info';
        foreach ($lines as $line) {
            $level = $this->detectLevel($line);
            if ($level === 'default') {
                if ($this->isStackTrace($line)) $level = $lastLevel;
            } else {
                $lastLevel = $level;
            }
            $levels[] = $level;
        }
        return $levels;
    }

    protected function detectLevel(string $line): string
    {
        $upper = strtoupper($line);
        if (str_contains($upper, 'ERROR') || str_contains($upper, 'FATAL') || str_contains($upper, 'SEVERE')) return 'error';
        if (str_contains($upper, 'WARN')) return 'warn';
        if (str_contains($upper, 'INFO')) return 'info';
        if (str_contains($upper, 'DEBUG') || str_contains($upper, 'TRACE')) return 'debug';
        return 'default';
    }

    protected function isStackTrace(string $line): bool
    {
        $trimmed = ltrim($line);
        return str_starts_with($trimmed, 'at ')
            || str_starts_with($trimmed, 'Caused by:')
            || str_starts_with($trimmed, '...')
            || preg_match('/^\s+at\s/', $line)
            || preg_match('/^\s*\.\.\.\s\d+\smore/', $line)
            || preg_match('/^[a-zA-Z][\w.]+\.(Exception|Error|Throwable)/', $trimmed)
            || preg_match('/^[a-zA-Z][\w.]+:\s/', $trimmed);
    }

    protected function loadLogFiles(): void
    {
        try {
            $server = filament()->getTenant();
            $fileRepository = app(DaemonFileRepository::class)->setServer($server);
            $this->logFiles = [];

            $rootFiles = $fileRepository->getDirectory('/');
            foreach ($rootFiles as $file) {
                if ($this->isLogFile($file)) {
                    $this->logFiles[] = [
                        'name' => $file['name'],
                        'path' => '/' . $file['name'],
                        'size' => $file['size'] ?? 0,
                        'modified' => $file['modified_at'] ?? $file['modified'] ?? null,
                        'date' => $this->extractDate($file),
                    ];
                }
            }

            try {
                $logDirFiles = $fileRepository->getDirectory('/logs');
                foreach ($logDirFiles as $file) {
                    if ($this->isLogFile($file)) {
                        $this->logFiles[] = [
                            'name' => 'logs/' . $file['name'],
                            'path' => '/logs/' . $file['name'],
                            'size' => $file['size'] ?? 0,
                            'modified' => $file['modified_at'] ?? $file['modified'] ?? null,
                            'date' => $this->extractDate($file),
                        ];
                    }
                }
            } catch (\Exception $e) {}

            usort($this->logFiles, fn ($a, $b) => ($b['modified'] ?? '') <=> ($a['modified'] ?? ''));
            $this->filteredFiles = $this->logFiles;
        } catch (\Exception $e) {
            Log::error('LogViewer: Failed to load', ['error' => $e->getMessage()]);
        }
    }

    protected function isLogFile(array $file): bool
    {
        if (($file['is_file'] ?? true) === false || ($file['mimetype'] ?? '') === 'inode/directory') return false;
        $name = strtolower($file['name'] ?? '');
        if (str_ends_with($name, '.lck') || str_ends_with($name, '.lock')) return false;
        return str_ends_with($name, '.log') || str_ends_with($name, '.log.gz')
            || str_ends_with($name, '.log.1') || str_ends_with($name, '.log.2') || str_ends_with($name, '.log.3');
    }

    protected function extractDate(array $file): ?string
    {
        $modified = $file['modified_at'] ?? $file['modified'] ?? null;
        if ($modified) {
            try { return Carbon::parse($modified)->format('Y-m-d'); } catch (\Exception $e) {}
        }
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $file['name'] ?? '', $m)) return $m[1];
        return null;
    }

    public function filterFiles(): void
    {
        if (empty($this->dateFilter)) { $this->filteredFiles = $this->logFiles; return; }
        $d = Carbon::parse($this->dateFilter)->format('Y-m-d');
        $this->filteredFiles = array_values(array_filter($this->logFiles, fn ($f) => ($f['date'] ?? '') === $d));
        if ($this->selectedFile) {
            $found = false;
            foreach ($this->filteredFiles as $f) { if ($f['path'] === $this->selectedFile) { $found = true; break; } }
            if (!$found) { $this->selectedFile = null; }
        }
    }

    protected function getFileOptions(): array
    {
        $options = [];
        foreach ($this->filteredFiles as $file) {
            $size = $this->formatSize($file['size']);
            $date = $file['date'] ? Carbon::parse($file['date'])->format('d.m.Y') : '';
            $options[$file['path']] = "{$file['name']}  •  {$size}  •  {$date}";
        }
        return $options;
    }

    protected function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public function refreshFiles(): void
    {
        $this->loadLogFiles();
        $this->filterFiles();
        Notification::make()->title('Refreshed')->success()->send();
    }
}
