<?php

/*
 * Scoped datasets for browser tests (Pest docs, "Datasets > Scoped Datasets").
 * Pest loads this file automatically for tests in this folder.
 */

/** Window sizes the UI must work at: phone first (design.md), then desktop. */
dataset('widths', [
    'phone' => [390, 844],
    'desktop' => [1280, 800],
]);
