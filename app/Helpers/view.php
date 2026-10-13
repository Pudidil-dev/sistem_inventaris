<?php
declare(strict_types=1);

// Helper view: render template PHP di folder views/ beserta layout & partial

function render(string $view, array $data = [], string $layout = 'app'): void
{
    $content = render_to_string($view, $data);

    echo $layout === ''
        ? $content
        : render_to_string('layouts/' . $layout, [...$data, 'content' => $content]);
}

function render_to_string(string $view, array $data = []): string
{
    // Nama view hanya huruf kecil, angka, "/", "_" dan "-" (mencegah path traversal)
    $viewFile = BASE_PATH . '/views/' . $view . '.php';
    if (!preg_match('#^[a-z0-9/_-]+$#', $view) || !is_file($viewFile)) {
        throw new RuntimeException("View tidak ditemukan: {$view}");
    }

    extract($data, EXTR_SKIP);
    ob_start();

    try {
        require $viewFile;

        return (string) ob_get_clean();
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}

function partial(string $name, array $data = []): void
{
    echo render_to_string('partials/' . $name, $data);
}

function invalid_class(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

function selected(mixed $current, mixed $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? ' checked' : '';
}
