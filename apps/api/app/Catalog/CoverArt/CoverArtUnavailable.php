<?php

namespace App\Catalog\CoverArt;

use RuntimeException;

/**
 * The cover art source could not answer. The message never carries
 * credentials or tokens.
 */
final class CoverArtUnavailable extends RuntimeException {}
