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

/**
 * Window sizes with the dialog the user must get there: the vaul bottom drawer
 * on a phone, the centred Radix dialog on desktop (ResponsiveDialog switches at 768px).
 */
dataset('dialog variants', [
    'phone drawer' => [390, 844, '[data-slot="drawer-content"]'],
    'desktop dialog' => [1280, 800, '[data-slot="dialog-content"]'],
]);
