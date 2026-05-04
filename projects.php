<?php
/**
 * MediaWiki Projects API
 * Returns the list of supported MediaWiki projects for AChecker.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$projects_file = 'checker/js/mediawiki_projects.js';

if (!file_exists($projects_file)) {
    echo json_encode(['error' => 'Projects file not found']);
    exit;
}

$content = file_get_contents($projects_file);

// Extract the array from the JS variable
// Format: var AChecker_MW_Projects = [...];
if (preg_match('/var\s+AChecker_MW_Projects\s*=\s*(.*);/s', $content, $matches)) {
    echo $matches[1];
} else {
    echo json_encode(['error' => 'Could not parse projects data']);
}
?>
