<?php

namespace ErlandMuchasaj\LaravelFileUploader;

use Carbon\Carbon;
use ErlandMuchasaj\LaravelFileUploader\Exceptions\InvalidFile;
use ErlandMuchasaj\LaravelFileUploader\Exceptions\InvalidUpload;
use ErlandMuchasaj\LaravelFileUploader\Exceptions\MissingFile;
use ErlandMuchasaj\LaravelFileUploader\Exceptions\UploadFailed;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\WhitespacePathNormalizer;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FileUploader
{
    /**
     * Default file storage disk where files are stored
     */
    private static string $disk = 'public';

    /**
     * The cache of this.
     *
     * @var array<string, string>
     */
    protected static array $studlyCache = [];

    /**
     * default constants used to build file path
     */
    const UPLOAD_PATH = 'uploads/{user_id}/{type}/{filename}';

    /**
     * The public visibility setting.
     * - default
     *
     * @var string
     */
    const VISIBILITY_PUBLIC = 'public';

    /**
     * The private visibility setting.
     *
     * @var string
     */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * Available file folder for sizes
     */
    const ORIGINAL = 'original'; // this is not a directory just used as name

    const THUMB = 'thumb';

    const XSMALL = 'xs';

    const SMALL = 'sm';

    const MEDIUM = 'md';

    const LARGE = 'lg';

    const XLARGE = 'xl';

    /**
     * Available file types
     * suppoerted by fileService
     */
    const IMAGE = 'image';

    const AUDIO = 'audio';

    const VIDEO = 'video';

    const FILE = 'file';

    const FONT = 'font';

    const ARCHIVE = 'archive';

    const DOCUMENT = 'document';

    const SPREADSHEETS = 'spreadsheets';

    /**
     * Available file types
     *
     * @var array<int, string>
     */
    public static array $validTypes = [
        self::IMAGE,
        self::AUDIO,
        self::VIDEO,
        self::FILE,
        self::FONT,
        self::ARCHIVE,
        self::DOCUMENT,
        self::SPREADSHEETS,
    ];

    /**
     * Available sizes for images
     *
     * @var array<string, int>
     */
    public static array $validSizes = [
        self::THUMB => 60,
        self::XSMALL => 150,
        self::SMALL => 300,
        self::MEDIUM => 768,
        self::LARGE => 1024,
        self::XLARGE => 2048,
    ];

    /**
     * validOptions of visibility
     *
     * @var array<int, string>
     */
    public static array $validOptions = [
        self::VISIBILITY_PUBLIC,
        self::VISIBILITY_PRIVATE,
    ];

    /**
     * $image_ext
     *
     * @var array<int, string>
     */
    private static array $image_ext = ['jpg', 'pjpg', 'jpe', 'jpeg', 'png', 'bmp', 'gif', 'svg', 'svgz', 'tiff', 'tif', 'webp', 'ico', 'avif'];

    /**
     * $font_ext
     *
     * @var array<int, string>
     */
    private static array $font_ext = ['ttc', 'otf', 'ttf', 'woff', 'woff2'];

    /**
     * $audio_ext
     *
     * @var array<int, string>
     */
    private static array $audio_ext = ['mp3', 'm4a', 'ogg', 'mpga', 'wav'];

    /**
     * $video_ext
     *
     * @var array<int, string>
     */
    private static array $video_ext = ['smv', 'movie', 'mov', 'wvx', 'wmx', 'wm', 'mp4', 'mp4', 'mp4v', 'mpg4', 'mpeg', 'mpg', 'mpe', 'wmv', 'avi', 'ogv', '3gp', '3g2'];

    /**
     * $document_ext
     *
     * @var array<int, string>
     */
    private static array $document_ext = ['css', 'csv', 'html', 'htm', 'conf', 'log', 'txt', 'text', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'pps', 'ppsx', 'odt', 'xls', 'xlsx'];

    /**
     * $archive
     *
     * @var array<int, string>
     *
     * @example application/zip
     */
    private static array $archives_ext = ['gzip', 'rar', 'tar', 'zip', '7z'];

    /**
     * Upload a file into specified disk using
     * specified visibility and then store into DB.
     *
     * @param  array<string, string>  $args
     * @return array<string, mixed>
     *
     * @throws UploadFailed
     */
    public static function store(
        UploadedFile $file,
        array $args = []
    ): array {
        try {
            $data = self::upload($file, $args);
        } catch (Exception $e) {
            throw new UploadFailed($e->getMessage(), $e->getCode());
        }

        return $data;
    }

    /**
     * Upload an image to specific FileSystem
     *
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     *
     * @throws InvalidUpload
     * @throws InvalidFile
     */
    public static function upload(UploadedFile $file, array $options = []): array
    {
        if (! $file->isValid()) {
            throw new InvalidFile($file->getErrorMessage());
        }

        if ($file->getSize() === false) {
            throw new InvalidFile('File failed to load.');
        }

        //  // here you can put as many default values as you want.
        //  $defaults = [
        //      'disk' => self::$disk, // The disk where the file is being saved.
        //      'safe' => false, // weather or not to use safe client file operators
        //      'user_id' => 1, // files are grouped by user.
        //      'path' => self::UPLOAD_PATH, // Where files are being stored.
        //      'visibility' => self::VISIBILITY_PUBLIC, // public | private
        //  ];
        $defaults = self::getConfig();

        // merge default options with passed parameters
        $args = array_merge($defaults, $options);

        if (! in_array($args['visibility'], self::$validOptions, true)) {
            $args['visibility'] = self::VISIBILITY_PUBLIC;
        }

        $disk = $args['disk'] ?? self::$disk;

        $user_id = $args['user_id'] ?? null;

        /**
         * getClientOriginalName() and getClientOriginalExtension() are considered
         * unsafe therefor we use hashName() and extension()
         */
        // get filename with extension
        $filenameWithExtension = $file->getClientOriginalName();
        // $filenameWithExtension = $file->hashName() ?: $file->getClientOriginalName();

        // get file extension
        $extension = $file->getClientOriginalExtension();
        // $extension = $file->extension() ?: $file->getClientOriginalExtension();

        // get filename without extension
        $filename = pathinfo($filenameWithExtension, PATHINFO_FILENAME);

        // @todo - EM: Check filename normalizer
        $filename = (new WhitespacePathNormalizer)->normalizePath($filename);
        $filename = self::defaultSanitizer($filename);

        // filename to store
        $filenameToStore = $filename.'_'.time().'.'.$extension;

        //  Get the type of file we are storing
        $type = self::getType($extension);

        // Make a file path where image will be stored [uploads/{user_id}/{type}/{filename}.{ext}]
        $filePath = self::getUserDir($filenameToStore, $type, (int) $user_id);

        // Upload File to storage disk
        $fileContent = fopen($file, 'r+');
        if (! $fileContent) {
            throw new InvalidUpload('Could not read file from disk...');
        }

        $path = Storage::disk($disk)->put($filePath, $fileContent, $args['visibility']); // very nice for very big files

        // Store $filePath in the database
        if (! $path) {
            throw new InvalidUpload('The file could not be written to disk...');
        }

//        dd([
//            'type' => $type,
//            'extension' => $file->getClientOriginalExtension(),
//            '_extension' =>  $file->extension() ?: $file->getClientOriginalExtension(),
//            'name' => $filename,
//            'original_name' => $file->getClientOriginalName(),
//            'size' => $file->getSize(),
//            'mime_type' => $file->getClientMimeType(),
//            'dimensions' => self::getDimensions($file, $type),
//            'path' => $filePath,
//            'url' => Storage::disk($disk)->url($filePath),
//            'user_id' => $user_id,
//            'disk' => $disk,
//            'visibility' => $args['visibility'], // indicate if file is public or private
//            'hash_file' => self::getHashFile($file),
//            'uuid' => Str::uuid()->toString(),
//        ]);

        return [
            'type' => $type,
            'extension' => $file->getClientOriginalExtension(),
            '_extension' => $file->extension() ?: $file->getClientOriginalExtension(),
            'name' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'dimensions' => self::getDimensions($file, $type),
            'path' => $filePath,
            'url' => Storage::disk($disk)->url($filePath),
            'user_id' => $user_id,
            'disk' => $disk,
            'visibility' => $args['visibility'], // indicate if file is public or private
            'uuid' => Str::uuid()->toString(),
        ];
    }

    protected static function getUser(int $user_id = null): ?int
    {
        $defaults = self::getConfig();

        return $user_id ?? (! empty($defaults['user_id']) ? (int) $defaults['user_id'] : 1);
    }

    protected static function getDisk(string $disk = null): string
    {
        $key = $disk;

        if (isset(FileUploader::$studlyCache['disk_'.$key])) {
            return FileUploader::$studlyCache['disk_'.$key];
        }

        $defaults = self::getConfig();

        return FileUploader::$studlyCache['disk_'.$key] = $disk ?: $defaults['disk'];
    }

    /**
     * Get FileUploader configurations
     *
     * @return array<string, string>
     */
    protected static function getConfig(): array
    {
        $config = config(FileUploaderServiceProvider::$abstract);

        return empty($config) ? [] : Arr::wrap($config);
    }

    /**
     * Create a streamed response for a given file.
     *
     *
     * @return StreamedResponse Content
     */
    public static function get(string $path, string $disk = null): StreamedResponse
    {
        return Storage::disk(self::getDisk($disk))->response($path);
    }

    /**
     * Create a streamed response for a given file.
     *
     *
     * @return string|null Content
     *
     * @throws MissingFile
     */
    public static function getFile(string $path, string $disk = null): ?string
    {
        if (! Storage::disk(self::getDisk($disk))->exists($path)) {
            throw new MissingFile("File $path does not exists.");
        }

        return Storage::disk(self::getDisk($disk))->get($path);
    }

    /**
     * Get public path url.
     * Mainly used to access public files.
     */
    public static function url(string $path, string $disk = null): string
    {
        return Storage::disk(self::getDisk($disk))->url($path);
    }

    /**
     * Get file path.
     */
    public static function path(string $path, string $disk = null): string
    {
        return Storage::disk(self::getDisk($disk))->path($path);
    }

    /**
     * Download a specific resource
     *
     * @param  string|null  $name name with extensions.
     */
    public static function download(string $path, string|null $name, string $disk = null): StreamedResponse
    {
        return Storage::disk(self::getDisk($disk))->download($path, $name);
    }

    /**
     * Get Visibility of a file
     *
     * @return string public|private
     */
    public static function getVisibility(string $path, string $disk = null): string
    {
        return Storage::disk(self::getDisk($disk))->getVisibility($path);
    }

    /**
     * Set Visibility of a file
     */
    public static function setVisibility(string $path, string $visibility, string $disk = null): bool
    {
        if (! in_array($visibility, self::$validOptions, true)) {
            return false;
        }

        return Storage::disk(self::getDisk($disk))->setVisibility($path, $visibility);
    }

    /**
     * Delete file from disk
     *
     * @throws MissingFile
     */
    public static function remove(string $path, bool $throwError = true, string $disk = null): bool
    {
        if ($throwError && ! Storage::disk(self::getDisk($disk))->exists($path)) {
            throw new MissingFile('File does not exist!');
        }

        return Storage::disk(self::getDisk($disk))->delete($path);
    }

    /**
     * Get meta data for a specific file.
     *
     *
     * @return array<string, mixed>
     */
    public static function meta(string $path, string $disk = null): array
    {
        $diskFrom = Storage::disk(self::getDisk($disk));

        return [
            'path' => $diskFrom->path($path),
            'url' => $diskFrom->url($path),
            'visibility' => $diskFrom->getVisibility($path),
            'mimeType' => $diskFrom->mimeType($path),
            'size' => self::formatBytes($diskFrom->size($path)),
            'last_modified' => Carbon::createFromTimestamp($diskFrom->lastModified($path))->diffForHumans(),
            'name' => basename($path),
        ];
    }

    /**
     * Get all extensions
     *
     * @return array<int, string> Extensions of all file types
     */
    public static function allExtensions(): array
    {
        return array_merge(self::$image_ext, self::$audio_ext, self::$video_ext, self::$document_ext, self::$archives_ext, self::$font_ext);
    }

    /**
     * Get all allowed  image extensions
     *
     * @return array<int, string>
     */
    public static function images(): array
    {
        return self::$image_ext;
    }

    /**
     * Get all allowed document extensions
     *
     * @return array<int, string>
     */
    public static function documents(): array
    {
        return self::$document_ext;
    }

    /**
     * Get type by extension
     *
     * @param  string  $ext Specific extension
     * @return string Type
     */
    private static function getType(string $ext): string
    {
        if (self::in_array($ext, self::$image_ext)) {
            return self::IMAGE;
        }

        if (self::in_array($ext, self::$audio_ext)) {
            return self::AUDIO;
        }

        if (self::in_array($ext, self::$video_ext)) {
            return self::VIDEO;
        }

        if (self::in_array($ext, self::$document_ext)) {
            return self::DOCUMENT;
        }

        if (in_array($ext, self::$font_ext)) {
            return self::FONT;
        }

        if (in_array($ext, self::$archives_ext)) {
            return self::ARCHIVE;
        }

        return self::FILE;
    }

    private static function defaultSanitizer(string $fileName): string
    {
        $fileName = (string) preg_replace('#\p{C}+#u', '', $fileName);

        return str_replace(['#', '/', '\\', ' '], '-', $fileName);
    }

    /**
     * Get directory for the specific user
     *
     * @return string Specific user directory
     *
     * @example uploads/{user_id}/{type}/{filename}
     */
    private static function getUserDir(string $filename, string $type = self::FILE, int $user_id = null): string
    {
        $defaults = self::getConfig();

        $dir = $defaults['path'] ?? self::UPLOAD_PATH;

        return trim(strtr($dir, [
            '{user_id}' => $user_id,
            '{type}' => $type,
            '{filename}' => $filename,
        ]), '/\\');
    }

    /**
     * Grab dimensions of an image.
     *
     * @return string|null string|null
     */
    private static function getDimensions(UploadedFile $file, string $type = self::IMAGE): ?string
    {
        if ('image' !== $type) {
            return null;
        }

        if (self::isValidFileInstance($file) && $file->getClientMimeType() === 'image/svg+xml') {
            return null;
        }

        if (! self::isValidFileInstance($file) || ! $sizeDetails = @getimagesize($file->getRealPath())) {
            return null;
        }

        [$width, $height] = $sizeDetails;

        return $width.'x'.$height;
    }

    /**
     * Check that the given value is a valid file instance.
     */
    private static function isValidFileInstance(mixed $file): bool
    {
        if ($file instanceof SymfonyUploadedFile && ! $file->isValid()) {
            return false;
        }

        return $file instanceof SymfonyFile;
    }

    /**
     * @param  array<string>  $haystack
     */
    private static function in_array(string $needle, array $haystack): bool
    {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }

    /**
     * get icon path
     */
    public static function getIconPath(string $mimeType): string
    {
        $file_type_icons_path = 'img'.DIRECTORY_SEPARATOR.'file-type-icons'.DIRECTORY_SEPARATOR;

        $icon_file = match ($mimeType) {
            'image/jpeg', 'image/pjpeg', 'image/x-jps' => 'jpeg.png',
            'image/png' => 'png.png',
            'image/gif' => 'gif.png',
            'image/bmp', 'image/x-windows-bmp' => 'bmp.png',
            'text/html', 'text/asp', 'text/javascript', 'text/ecmascript', 'application/x-javascript', 'application/javascript', 'application/ecmascript' => 'html.png',
            'text/plain' => 'conf.png',
            'text/css' => 'css.png',
            'audio/aiff', 'audio/x-aiff', 'audio/midi' => 'midi.png',
            'application/x-troff-msvideo', 'video/avi', 'video/msvideo', 'video/x-msvideo', 'video/avs-video' => 'avi.png',
            'video/animaflex' => 'fla.png',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-word.document.macroEnabled.12', 'application/vnd.ms-word.template.macroEnabled.12', 'application/vnd.oasis.opendocument.text', 'application/vnd.apple.pages', 'application/vnd.ms-xpsdocument', 'application/oxps', 'application/rtf', 'application/wordperfect', 'application/octet-stream' => 'docx.png',
            'application/x-compressed', 'application/x-7z-compressed', 'application/x-gzip', 'application/zip', 'multipart/x-gzip', 'multipart/x-zip' => 'zip.png',
            'application/x-gtar', 'application/rar', 'application/x-tar' => 'rar.png',
            'video/mpeg', 'audio/mpeg' => 'mpeg.png',
            'application/pdf' => 'pdf.png',
            'application/mspowerpoint', 'application/vnd.ms-powerpoint', 'application/powerpoint' => 'ms-pptx.png',
            'application/excel', 'application/x-excel', 'application/x-msexcel', 'application/vnd.apple.numbers', 'application/application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.ms-excel.sheet.macroEnabled.12', 'application/vnd.ms-excel.sheet.binary.macroEnabled.12', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'ms-xlsx.png',
            'image/vnd.adobe.photoshop' => 'psd.png',
            'not-found' => 'not-found.png',
            default => 'unknown.png',
        };

        return $file_type_icons_path.$icon_file;
    }

    /**
     * getFileType
     * Return file mimetype default: 'application/octet-stream'
     */
    public static function getFileType(string $filename): bool|string
    {
        $mime_types = [

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms-office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        $arr = explode('.', $filename);
        $ext = strtolower(array_pop($arr));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $fileInfo = finfo_open(FILEINFO_MIME);
            if ($fileInfo) {
                $mimetype = finfo_file($fileInfo, $filename);
                finfo_close($fileInfo);

                return $mimetype;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * helper to format bytes to other units
     *
     * @param  int  $size in-bytes
     */
    public static function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $bytes = max($size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * converts KB,MB,GB,TB,PB,EB,ZB,YB to bytes
     *
     *
     * @example 1KB => 1000 (bytes)
     */
    public static function convertToBytes(string $from): float|int|string
    {
        $number = (int) substr($from, 0, -2);

        return match (strtoupper(substr($from, -2))) {
            'KB' => $number * 1024,
            'MB' => $number * pow(1024, 2),
            'GB' => $number * pow(1024, 3),
            'TB' => $number * pow(1024, 4),
            'PB' => $number * pow(1024, 5),
            'EB' => $number * pow(1024, 6),
            'ZB' => $number * pow(1024, 7),
            'YB' => $number * pow(1024, 8),
            default => $from,
        };
    }

    /**
     * Convert bytes to the unit specified by the $to parameter.
     *
     * @param  int  $bytes  The filesize in Bytes.
     * @param  string  $to  The unit type to convert to. Accepts KB, MB, GB, TB or PB for Kilobytes, Megabytes, Gigabytes, Terabytes or PetaBytes, respectively.
     * @param  int  $decimal_places  The number of decimal places to return.
     * @return string Returns only the number of units, not the type letter. Returns 0 if the $to unit type is out of scope.
     *
     * @example 1024 (KB) => 1MB
     */
    public static function convertBytesToSpecified(int $bytes, string $to = 'MB', int $decimal_places = 2): string
    {
        $formulas = [
            'KB' => number_format($bytes / 1024, $decimal_places),
            'MB' => number_format($bytes / pow(1024, 2), $decimal_places),
            'GB' => number_format($bytes / pow(1024, 3), $decimal_places),
            'TB' => number_format($bytes / pow(1024, 4), $decimal_places),
            'PB' => number_format($bytes / pow(1024, 5), $decimal_places),
            'EB' => number_format($bytes / pow(1024, 6), $decimal_places),
            'ZB' => number_format($bytes / pow(1024, 7), $decimal_places),
            'YB' => number_format($bytes / pow(1024, 8), $decimal_places),
        ];

        return isset($formulas[$to]) ? $formulas[$to].$to : 0 .$to;
    }
}
