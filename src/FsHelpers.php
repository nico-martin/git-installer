<?php

namespace SayHello\GitInstaller;

class FsHelpers
{
    public static function moveDir($from, $to): bool
    {
        return @rename(
            $from,
            $to
        );
    }

    public static function removeDir($dir)
    {
        if (is_link($dir) || is_file($dir)) {
            unlink($dir);
            return;
        }

        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === "." || $object === "..") {
                continue;
            }

            $path = $dir . "/" . $object;
            if (is_dir($path) && !is_link($path)) {
                self::removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public static function replaceDir($staged, $target)
    {
        $hasTarget = file_exists($target) || is_link($target);
        $backup = $target . '.shgi-backup-' . wp_generate_uuid4();

        if ($hasTarget && !self::moveDir($target, $backup)) {
            self::removeDir($staged);
            return new \WP_Error(
                'shgi_target_backup_failed',
                sprintf(
                    // translators: %s: Existing package directory path.
                    __('The existing package directory %s could not be backed up', 'shgi'),
                    $target
                )
            );
        }

        if (self::moveDir($staged, $target)) {
            if ($hasTarget) {
                self::removeDir($backup);
            }
            return true;
        }

        if ($hasTarget && !self::moveDir($backup, $target)) {
            return new \WP_Error(
                'shgi_target_restore_failed',
                sprintf(
                    // translators: %s: Backup directory path.
                    __('The package update failed and the backup at %s could not be restored', 'shgi'),
                    $backup
                )
            );
        }

        self::removeDir($staged);
        return new \WP_Error(
            'shgi_target_replace_failed',
            __('The new package directory could not replace the existing package', 'shgi')
        );
    }

    public static function unzip($zipFile, $dest)
    {
        $zip = new \ZipArchive;
        $res = $zip->open($zipFile);
        if ($res !== true) {
            return new \WP_Error(
                'shgi_repo_unzip_failed',
                sprintf(
                    // translators: %s: ZipArchive error code.
                    __('The package archive could not be unpacked (ZipArchive error %s)', 'shgi'),
                    $res
                )
            );
        }

        if (!$zip->extractTo($dest)) {
            $zip->close();
            return new \WP_Error(
                'shgi_repo_unzip_failed',
                __('The package archive could not be extracted', 'shgi')
            );
        }

        $zip->close();
        unlink($zipFile);
        return true;
    }

    public static function isInMaintenanceMode()
    {
        $file = ABSPATH . '.maintenance';
        return file_exists($file);
    }

    public static function maintenanceMode($enable = false)
    {
        $file = ABSPATH . '.maintenance';
        if ($enable) {
            $maintenance_string = '<?php $upgrading = ' . time() . '; ?>';
            file_put_contents($file, $maintenance_string);
        } elseif (!$enable && file_exists($file)) {
            unlink($file);
        }
    }
}
