<?php

function set_message( string $text, string $type = 'success' ) : void {
	$_SESSION['message'] = compact('type', 'text');
}

function get_message() : ?array {
	$message = $_SESSION['message'] ?? null;
	unset($_SESSION['message']);
	return $message;
}

function get_ip() {
	return $_SERVER['REMOTE_ADDR'] ?? '';
}

function is_debug_ip() {
	return defined('DEBUG_IPS') && in_array(get_ip(), DEBUG_IPS);
}

function html_asset( $src ) {
	$local = is_int(strpos($_SERVER['HTTP_HOST'], 'home.'));
	$mobile = is_int(stripos($_SERVER['HTTP_USER_AGENT'], 'mobile'));
	$buster = $local && !$mobile ? '' : '?_' . filemtime($src);
	return $src . $buster;
}

function csv_escape( $val ) {
	return str_replace('"', '""', $val);
}

function csv_row( $data ) {
	return '"' . implode('","', array_map('csv_escape', $data)) . '"' . "\r\n";
}

function csv_cols( $data ) {
	$cols = array();
	foreach ( $data as $i => $name ) {
		$cols[] = !is_int($i) && is_callable($name) ? $i : $name;
	}
	return $cols;
}

function csv_rows( $data ) {
	return implode(array_map('csv_row', $data));
}

function csv_header( $filename = '' ) {
	header('Content-Type: text/plain; charset=utf-8');

	if ( $filename ) {
		header('Content-Disposition: attachment; filename="' . $filename . '"');
	}
}

function csv_file( $data, $cols, $filename = '' ) {
	csv_header($filename);

	echo csv_row(csv_cols($cols));
	foreach ( $data AS $row ) {
		$data = array();
		foreach ( $cols as $i => $name ) {
			$data[] = !is_int($i) && is_callable($name) ? $name($row) : $row->$name;
		}
		echo csv_row($data);
	}

	if ( $filename ) {
		exit;
	}
}

function html_options( $options, $selected = null, $empty = '', $datalist = false ) {
	$selected = (array) $selected;

	$html = '';

	$isSelected = in_array('', $selected, true) ? ' selected' : '';
	$empty && $html .= '<option value=""' . $isSelected . '>' . $empty . '</option>';

	foreach ( $options AS $value => $label ) {
		if ( is_array($label) ) {
			$html .= '<optgroup label="' .  html($value) . '">';
			foreach ( $label as $value2 => $label2) {
				$isSelected = in_array($value2, $selected) ? ' selected' : '';
				$html .= '<option value="' . html($value2) . '"' . $isSelected . '>' . html($label2) . '</option>';
			}
			$html .= '</optgroup>';
		}
		else {
			$isSelected = in_array($value, $selected) ? ' selected' : '';
			$value = $datalist ? html($label) : html($value);
			$label = $datalist ? '' : html($label);
			$html .= '<option value="' . $value . '"' . $isSelected . '>' . $label . '</option>';
		}
	}
	return $html;
}

function get_date( $date ) {
	$components = $date ? array_map('intval', explode('-', $date)) : [0, 0];

	// With month
	if ( $components[1] ) {
		$utc = mktime(0, 0, 0, $components[1], 1, $components[0]);
		return date('M Y', $utc);
	}

	// Only year
	elseif ( $components[0] ) {
		return $components[0];
	}

	return '';
}

function get_url( $path, $query = array() ) {
	$query = $query ? '?' . http_build_query($query) : '';
	$path = $path ? $path . '.php' : basename($_SERVER['SCRIPT_NAME']);
	return $path . $query;
}

function do_redirect( $path, $query = array() ) {
	$url = get_url($path, $query);
	header('Location: ' . $url);
}

function html( $text ) {
	return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8') ?: htmlspecialchars((string)$text, ENT_QUOTES, 'ISO-8859-1');
}

function csv_read_doc( $data, $withHeader = true, $keepCols = array() ) {
	$keepCols and $keepCols = array_flip($keepCols);

	$header = array();
	$csv = array_map(function($line) use (&$header, $withHeader, $keepCols) {
		$data = str_getcsv(trim($line), ',', '"', '"');
		if ( $withHeader ) {
			if ( $header ) {
				$data = array_combine($header, $data);
				$keepCols and $data = array_intersect_key($data, $keepCols);
			}
			else {
				$header = $data;
			}
		}
		return $data;
	}, explode("\n", trim($data)));
	$withHeader and $csv = array_slice($csv, 1);
	return $csv;
}
