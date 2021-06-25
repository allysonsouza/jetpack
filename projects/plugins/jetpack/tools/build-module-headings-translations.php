<?php

// phpcs:disable WordPress.WP.AlternativeFunctions

$file_contents = "<?php
/**
 * Do not edit this file. It's generated by `jetpack/tools/build-module-headings-translations.php`.
 *
 * @package automattic/jetpack
 */

// Pointless to do two passes over everything just for alignment.
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned

/**
 * For a given module, return an array with translated name and description.
 *
 * @param string \$key Module file name without `.php`.
 *
 * @return array
 */
function jetpack_get_module_i18n( \$key ) {
\tstatic \$modules;
\tif ( ! isset( \$modules ) ) {
\t\t\$modules = array(";

$jp_dir = dirname( dirname( __FILE__ ) ) . '/';

$files  = glob( "{$jp_dir}modules/*.php" );
$tags   = array(
	'Other' => array(),
);
foreach ( $files as $file ) {
	$absolute_path  = $file;
	$relative_path  = str_replace( $jp_dir, '', $file );
	$_file_contents = '';

	$file      = fopen( $absolute_path, 'r' );
	$file_data = fread( $file, 8192 );
	fclose( $file );

	// Make sure we catch CR-only line endings.
	$file_data = str_replace( "\r", "\n", $file_data );

	$all_headers = array(
		'name'        => 'Module Name',
		'description' => 'Module Description',
		'tags'        => 'Module Tags',
	);

	foreach ( $all_headers as $field => $regex ) {
		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$string = trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $match[1] ) );
			$string = addcslashes( $string, "''" );
			if ( 'Module Tags' === $regex ) {
				$module_tags = array_map( 'trim', explode( ',', $string ) );
				foreach ( $module_tags as $tag ) {
					$tags[ $tag ][] = $relative_path;
				}
			} else {
				$_file_contents .= "\t\t\t\t'{$field}' => _x( '{$string}', '{$regex}', 'jetpack' ),\n";
			}
		}
	}

	if ( $_file_contents ) {
		$file_contents .= "\n\t\t\t'" . str_replace( '.php', '', basename( $absolute_path ) ) . "' => array(\n$_file_contents\t\t\t),\n";
	}

}
$file_contents .= "\t\t);
\t}";
$file_contents .= "\n\treturn \$modules[ \$key ];
}";

$file_contents .= "

// The lists of filenames below shouldn't be arbitrarily punctuated, but the sniff triggers anyway.
// phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar

/**
 * For a given module tag, return its translated version.
 *
 * @param string \$key Module tag as is in each module heading.
 *
 * @return string
 */";
$file_contents .= "\nfunction jetpack_get_module_i18n_tag( \$key ) {
\tstatic \$module_tags;
\tif ( ! isset( \$module_tags ) ) {";
$file_contents .= "\n\t\t\$module_tags = array(";
foreach ( $tags as $tag_name => $tag_files ) {
	$file_contents .= "\n\t\t\t// Modules with `{$tag_name}` tag:\n";
	foreach ( $tag_files as $file ) {
		$file_contents .= "\t\t\t// - {$file}\n";
	}
	$file_contents .= "\t\t\t'{$tag_name}' => _x( '{$tag_name}', 'Module Tag', 'jetpack' ),\n";
}
$file_contents .= "\t\t);
\t}";
$file_contents .= "\n\treturn ! empty( \$module_tags[ \$key ] ) ? \$module_tags[ \$key ] : '';
}\n";

$all_headers = array(
	'name'                      => 'Module Name',
	'description'               => 'Module Description',
	'sort'                      => 'Sort Order',
	'recommendation_order'      => 'Recommendation Order',
	'introduced'                => 'First Introduced',
	'changed'                   => 'Major Changes In',
	'deactivate'                => 'Deactivate',
	'free'                      => 'Free',
	'requires_connection'       => 'Requires Connection',
	'requires_user_connection'  => 'Requires User Connection',
	'auto_activate'             => 'Auto Activate',
	'module_tags'               => 'Module Tags',
	'feature'                   => 'Feature',
	'additional_search_queries' => 'Additional Search Queries',
	'plan_classes'              => 'Plans',
);

/*
 * Create the jetpack_get_module_info function.
 */
$file_contents .= "
/**
 * For a given module, return an array with the module info.
 *
 * @param string \$key Module file name without `.php`.
 *
 * return array|string An array containing the module info or an empty string if the given module isn't known.
 */
function jetpack_get_module_info( \$key ) {
\tstatic \$module_info;
\tif ( ! isset( \$module_info ) ) {
\t\t\$module_info = array(";

foreach ( $files as $file ) {
	$absolute_path  = $file;
	$relative_path  = str_replace( $jp_dir, '', $file );
	$_file_contents = '';

	$file      = fopen( $absolute_path, 'r' );
	$file_data = fread( $file, 8192 );
	fclose( $file );

	// Make sure we catch CR-only line endings.
	$file_data = str_replace( "\r", "\n", $file_data );

	foreach ( $all_headers as $field => $regex ) {
		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$string = trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $match[1] ) );
			$string = addcslashes( $string, "''" );

			$_file_contents .= "\t\t\t\t'{$field}' => '{$string}',\n";
		} else {
			$_file_contents .= "\t\t\t\t'{$field}' => '',\n";
		}
	}

	if ( $_file_contents ) {
		$file_contents .= "\n\t\t\t'" . str_replace( '.php', '', basename( $absolute_path ) ) . "' => array(\n$_file_contents\t\t\t),\n";
	}
}
$file_contents .= "\t\t);
\t}";
$file_contents .= "\n\treturn ! empty( \$module_info[ \$key ] ) ? \$module_info[ \$key ] : '';
}\n";

/*
 * Create the jetpack_get_all_module_header_names function.
 */
$file_contents .= "
/**
 * Return an array containing all module header names.
 *
 * @return array
 */
function jetpack_get_all_module_header_names() {
\treturn array(\n";

foreach ( $all_headers as $field => $regex ) {
	$file_contents .= "\t\t'{$field}' => '{$regex}',\n";
}

$file_contents .= "\t);
}\n";

file_put_contents( "{$jp_dir}modules/module-headings.php", $file_contents );
