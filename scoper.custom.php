<?php

function customize_php_scoper_config( array $config ): array {
    $config['patchers'][] = function( string $filePath, string $prefix, string $content ): string {
        if ( strpos( $filePath, 'rebelcode/wp-http/src/WpHandler.php' ) !== false ) {
            $content = str_replace( ' wp_', ' \\wp_', $content );
        }

        return $content;
    };

    return $config;
}