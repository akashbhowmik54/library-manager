<?php

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

class Library_CLI_Commands {

    /**
     * Import books from a JSON file.
     */
    public function import( $args ) {

        list( $file ) = $args;

        if ( ! file_exists( $file ) ) {
            WP_CLI::error( "File not found: $file" );
        }

        $json = file_get_contents( $file );
        $books = json_decode( $json, true );

        if ( ! is_array( $books ) ) {
            WP_CLI::error( "Invalid JSON format." );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'library_books';

        WP_CLI::log( "Importing " . count($books) . " books..." );

        foreach ( $books as $book ) {

            $wpdb->insert(
                $table,
                [
                    'title'            => sanitize_text_field( $book['title'] ?? '' ),
                    'author'           => sanitize_text_field( $book['author'] ?? '' ),
                    'description'      => sanitize_textarea_field( $book['description'] ?? '' ),
                    'publication_year' => intval( $book['publication_year'] ?? 0 ),
                    'status'           => sanitize_text_field( $book['status'] ?? 'available' ),
                ]
            );
        }

        WP_CLI::success( "Import complete!" );
    }
}
