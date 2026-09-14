<?php
/**
 * Close / remove "X" icon used by the metering controls (chip, condition, group).
 *
 * @package memberful-wp
 *
 * @var int $size Pixel size (default 16). The smaller 12px chip variant uses a heavier stroke.
 */

$size   = isset( $size ) ? $size : 16;
$stroke = $size <= 12 ? '2.5' : '2';
?>
<svg width="<?php echo esc_attr( $size ); ?>" height="<?php echo esc_attr( $size ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?php echo esc_attr( $stroke ); ?>" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
