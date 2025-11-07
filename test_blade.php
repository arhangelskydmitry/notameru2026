<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $post = \App\Models\Post::first();
    if (!$post) {
        echo "No posts found in database\n";
        exit(1);
    }
    
    $relatedPosts = collect([]);
    
    $view = view('frontend.post', compact('post', 'relatedPosts'));
    $html = $view->render();
    
    echo "✓ View compiled successfully!\n";
    echo "HTML length: " . strlen($html) . " characters\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

