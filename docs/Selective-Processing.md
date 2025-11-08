# Selective Variant Processing

The `processOnlyTheseVariants()` method allows you to selectively process only specific image variants, which is useful for optimization and re-processing scenarios.

## How It Works

When you call `process()` on the `ImageProcessor`, it will normally process **all** variants defined in your file. However, you can use `processOnlyTheseVariants()` to filter which variants get processed.

## Basic Usage

### Process All Variants (Default)

```php
// These are equivalent - both process all variants:

// Option 1: Don't use the filter at all
$file = $imageProcessor->process($file);

// Option 2: Pass empty array
$file = $imageProcessor
    ->processOnlyTheseVariants([])
    ->process($file);

// Option 3: Explicitly reset the filter
$file = $imageProcessor
    ->processAll()
    ->process($file);
```

### Process Only Specific Variants

```php
// Define multiple variants
$collection = ImageVariantCollection::create();
$collection->addNew('thumbnail')->scale(300, 300);
$collection->addNew('avatar')->cover(150, 150);
$collection->addNew('large')->scale(1920, 1080);
$collection->addNew('medium')->scale(1024, 768);

$file = $file->withVariants($collection->toArray());

// Process ONLY the thumbnail and avatar variants
// (large and medium are skipped)
$file = $imageProcessor
    ->processOnlyTheseVariants(['thumbnail', 'avatar'])
    ->process($file);
```

## Real-World Use Cases

### 1. **Progressive Upload Processing**

Process smaller variants first for quick preview, then process larger ones in the background.

```php
// Step 1: Quick processing for immediate display
$file = $imageProcessor
    ->processOnlyTheseVariants(['thumbnail', 'avatar'])
    ->process($file);

// User can now see thumbnail immediately
displayToUser($file);

// Step 2: Background job processes remaining variants
$file = $imageProcessor
    ->processOnlyTheseVariants(['large', 'medium', 'small'])
    ->process($file);
```

### 2. **Re-generating Single Variants**

If you change the configuration for one variant, re-process only that variant without touching the others.

```php
// Original processing created all variants
$file = $imageProcessor->process($file);

// Later: You update the thumbnail settings to be larger
// Re-process ONLY the thumbnail, keep others unchanged
$collection = ImageVariantCollection::create();
$collection->addNew('thumbnail')->scale(400, 400);  // Changed from 300

$file = $file->withVariants($collection->toArray());

$file = $imageProcessor
    ->processOnlyTheseVariants(['thumbnail'])  // Only regenerate thumbnail
    ->process($file);
```

### 3. **Conditional Processing Based on File Type**

Process different variants based on the uploaded file characteristics.

```php
// Get image dimensions
$dimensions = getimagesize($originalFile);
$width = $dimensions[0];

if ($width < 1024) {
    // Small image - only create thumbnail
    $file = $imageProcessor
        ->processOnlyTheseVariants(['thumbnail'])
        ->process($file);
} else {
    // Large image - create all variants
    $file = $imageProcessor->process($file);
}
```

### 4. **Queue-Based Processing**

Distribute variant processing across multiple queue jobs.

```php
// Queue Job 1: Process thumbnails (quick)
public function processQuickVariants($fileId) {
    $file = $this->fileStorage->getById($fileId);
    $file = $imageProcessor
        ->processOnlyTheseVariants(['thumbnail', 'avatar'])
        ->process($file);
}

// Queue Job 2: Process large variants (slower)
public function processLargeVariants($fileId) {
    $file = $this->fileStorage->getById($fileId);
    $file = $imageProcessor
        ->processOnlyTheseVariants(['large', 'xlarge'])
        ->process($file);
}
```

### 5. **Development/Testing**

During development, process only the variants you're working on to save time.

```php
if (IS_DEVELOPMENT) {
    // Only process thumbnail during development for faster iterations
    $file = $imageProcessor
        ->processOnlyTheseVariants(['thumbnail'])
        ->process($file);
} else {
    // Production: process everything
    $file = $imageProcessor->process($file);
}
```

## Important Notes

### The Filter is Stateful

The filter persists on the `ImageProcessor` instance until you change it:

```php
// Set filter
$imageProcessor->processOnlyTheseVariants(['thumbnail']);

// This processes only thumbnail
$file1 = $imageProcessor->process($file1);

// This ALSO processes only thumbnail (filter still active!)
$file2 = $imageProcessor->process($file2);

// Reset to process all
$imageProcessor->processAll();

// Now this processes all variants
$file3 = $imageProcessor->process($file3);
```

**Best Practice:** Always explicitly set the filter or reset it before each `process()` call to avoid confusion:

```php
// Good: Explicit
$file = $imageProcessor
    ->processOnlyTheseVariants(['thumbnail'])
    ->process($file);

// Also good: Explicit reset
$file = $imageProcessor
    ->processAll()
    ->process($file);
```

### Variant Must Be Defined

The processor only processes variants that are defined in the file's variant collection. If you specify a variant name that doesn't exist, it's simply ignored:

```php
$collection = ImageVariantCollection::create();
$collection->addNew('thumbnail')->scale(300, 300);
$file = $file->withVariants($collection->toArray());

// This will only process 'thumbnail'
// 'nonexistent' is ignored (no error thrown)
$file = $imageProcessor
    ->processOnlyTheseVariants(['thumbnail', 'nonexistent'])
    ->process($file);
```

### Empty Operations are Skipped

Even if you specify a variant name, if that variant has no operations defined, it won't be processed:

```php
$collection = ImageVariantCollection::create();
$collection->addNew('empty');  // No operations added!
$file = $file->withVariants($collection->toArray());

// Nothing happens - 'empty' has no operations
$file = $imageProcessor
    ->processOnlyTheseVariants(['empty'])
    ->process($file);
```

## Method Reference

### `processOnlyTheseVariants(array $variants): self`

Sets a filter to process only the specified variants.

**Parameters:**
- `$variants` - Array of variant names to process

**Returns:** `$this` for method chaining

**Example:**
```php
$imageProcessor->processOnlyTheseVariants(['thumbnail', 'avatar']);
```

### `processAll(): self`

Resets the filter to process all variants (default behavior).

**Returns:** `$this` for method chaining

**Example:**
```php
$imageProcessor->processAll();
```

## Performance Tips

1. **Process lightweight variants first** - Thumbnails and small images process faster, giving users quicker feedback
2. **Use queues for large variants** - Process high-resolution variants in background jobs
3. **Skip unnecessary variants** - Don't process variants you know won't be used
4. **Re-process selectively** - When updating variants, only regenerate what changed

## Complete Example

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;

// Define all variants
$collection = ImageVariantCollection::create();
$collection->addNew('thumbnail')->scale(300, 300)->optimize();
$collection->addNew('avatar')->cover(150, 150)->optimize();
$collection->addNew('large')->scale(1920, 1080)->optimize();
$collection->addNew('medium')->scale(1024, 768)->optimize();

$file = $file->withVariants($collection->toArray());

// Strategy 1: Process all at once (simple but slow)
$file = $imageProcessor->process($file);

// Strategy 2: Quick preview, then full processing (better UX)
// Step 1: Fast variants for immediate display
$file = $imageProcessor
    ->processOnlyTheseVariants(['thumbnail', 'avatar'])
    ->process($file);

// Show user the thumbnail while processing continues
displayPreview($file);

// Step 2: Process remaining variants in background
dispatch(new ProcessRemainingVariantsJob($file->uuid, ['large', 'medium']));
```
