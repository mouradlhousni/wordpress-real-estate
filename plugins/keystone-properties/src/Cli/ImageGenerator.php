<?php
/**
 * Procedural demo images (no external downloads, no hotlinking).
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Cli;

defined( 'ABSPATH' ) || exit;

/**
 * Draws flat, architectural-style illustrations with GD.
 *
 * Images are drawn at 1.5x and downsampled, which gives cheap anti-aliasing.
 * A tiny seeded PRNG keeps output deterministic: the same seed always yields the same image.
 */
final class ImageGenerator {

	private const SUPERSAMPLE = 1.5;

	/**
	 * GD canvas.
	 *
	 * @var \GdImage
	 */
	private $img;

	/**
	 * Canvas width (supersampled).
	 *
	 * @var int
	 */
	private int $w = 0;

	/**
	 * Canvas height (supersampled).
	 *
	 * @var int
	 */
	private int $h = 0;

	/**
	 * PRNG state.
	 *
	 * @var int
	 */
	private int $state = 1;

	/**
	 * Sky palettes: top, bottom, ground near, ground far, accent (sun).
	 */
	private const SKIES = array(
		'day'    => array( '#6fa3d6', '#dcebf6', '#9fb07f', '#c4cfa4', '#fff6d8' ),
		'golden' => array( '#e9a663', '#fbe6c6', '#b39a6c', '#d8c79c', '#fff1c9' ),
		'dusk'   => array( '#3f3e72', '#eb9a74', '#5e5a63', '#8d7f7f', '#ffd3a8' ),
		'mist'   => array( '#9fb2c4', '#eef1f2', '#8d9b8a', '#bcc5b6', '#ffffff' ),
	);

	/**
	 * Façade palettes: wall, wall shade, roof/trim, frame, accent.
	 */
	private const FACADES = array(
		array( '#f2ece2', '#d9d0c2', '#5b4136', '#3b3a3a', '#b8664a' ),
		array( '#f5f3ef', '#dcd8d1', '#2f3a45', '#2a2f36', '#c9a46a' ),
		array( '#e7d3bd', '#c9b39a', '#7c4a3a', '#3d3330', '#55704f' ),
		array( '#d8dde0', '#b7bec3', '#36404a', '#22282e', '#d08b5b' ),
		array( '#efe2cf', '#d6c4ab', '#8b5a43', '#4a3b33', '#46667a' ),
	);

	/**
	 * Whether GD is available.
	 *
	 * @return bool
	 */
	public static function is_supported(): bool {
		return function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagejpeg' );
	}

	/**
	 * Exterior illustration of a property type.
	 *
	 * @param string $type   apartment|house|villa|office.
	 * @param int    $seed   Seed.
	 * @param string $path   Output JPEG path.
	 * @param int    $width  Output width.
	 * @param int    $height Output height.
	 * @return bool
	 */
	public function exterior( string $type, int $seed, string $path, int $width = 1500, int $height = 1000 ): bool {
		$this->begin( $seed, $width, $height );

		$skies   = array_keys( self::SKIES );
		$sky     = self::SKIES[ $skies[ $this->rand( 0, count( $skies ) - 1 ) ] ];
		$facade  = self::FACADES[ $this->rand( 0, count( self::FACADES ) - 1 ) ];
		$horizon = (int) ( $this->h * 0.66 );

		$this->vertical_gradient( 0, 0, $this->w, $horizon, $sky[0], $sky[1] );
		$this->sun( $sky[4] );
		$this->clouds();
		$this->hills( $horizon, $this->mix( $sky[1], $sky[3], 0.55 ), 0.10 );
		$this->hills( $horizon, $this->mix( $sky[1], $sky[3], 0.85 ), 0.05 );
		$this->vertical_gradient( 0, $horizon, $this->w, $this->h, $sky[3], $sky[2] );

		$ground = (int) ( $this->h * 0.84 );

		switch ( $type ) {
			case 'apartment':
				$this->apartment( $ground, $facade, $sky );
				break;
			case 'villa':
				$this->villa( $ground, $facade, $sky );
				break;
			case 'office':
				$this->office( $ground, $facade, $sky );
				break;
			default:
				$this->house( $ground, $facade, $sky );
		}

		return $this->finish( $path );
	}

	/**
	 * Interior illustration.
	 *
	 * @param string $room   living|kitchen|bedroom.
	 * @param int    $seed   Seed.
	 * @param string $path   Output JPEG path.
	 * @param int    $width  Output width.
	 * @param int    $height Output height.
	 * @return bool
	 */
	public function interior( string $room, int $seed, string $path, int $width = 1500, int $height = 1000 ): bool {
		$this->begin( $seed, $width, $height );

		$walls   = array( array( '#f3eee6', '#e6ddcf' ), array( '#eef0ee', '#d9dfdb' ), array( '#f1e7dc', '#e2d1bf' ), array( '#e9ecef', '#d3d9de' ) );
		$floors  = array( array( '#c49a6c', '#a87d52' ), array( '#d8c3a5', '#bfa682' ), array( '#8e6a4f', '#73533c' ) );
		$wall    = $walls[ $this->rand( 0, count( $walls ) - 1 ) ];
		$floor   = $floors[ $this->rand( 0, count( $floors ) - 1 ) ];
		$floor_y = (int) ( $this->h * 0.70 );

		$this->vertical_gradient( 0, 0, $this->w, $floor_y, $wall[0], $wall[1] );
		$this->floor_planks( $floor_y, $floor );

		// Large window with a soft light shaft.
		$wx = (int) ( $this->w * ( 'kitchen' === $room ? 0.08 : 0.58 ) );
		$ww = (int) ( $this->w * 0.30 );
		$wy = (int) ( $this->h * 0.10 );
		$wh = (int) ( $this->h * 0.50 );
		$this->rect( $wx - 10, $wy - 10, $wx + $ww + 10, $wy + $wh + 10, '#ffffff' );
		$this->vertical_gradient( $wx, $wy, $wx + $ww, $wy + $wh, '#8fbde3', '#e6f2fa' );
		$this->hills_in_rect( $wx, $wy, $ww, $wh );
		$this->rect( $wx + (int) ( $ww / 2 ) - 4, $wy, $wx + (int) ( $ww / 2 ) + 4, $wy + $wh, '#ffffff' );
		$this->rect( $wx, $wy + (int) ( $wh * 0.62 ) - 4, $wx + $ww, $wy + (int) ( $wh * 0.62 ) + 4, '#ffffff' );
		$this->polygon(
			array( $wx, $wy + $wh, $wx + $ww, $wy + $wh, $wx + $ww + (int) ( $ww * 0.5 ), $this->h, $wx - (int) ( $ww * 0.2 ), $this->h ),
			'#ffffff',
			100
		);

		switch ( $room ) {
			case 'kitchen':
				$this->kitchen( $floor_y );
				break;
			case 'bedroom':
				$this->bedroom( $floor_y );
				break;
			default:
				$this->living( $floor_y );
		}

		return $this->finish( $path );
	}

	/**
	 * Stylised agent portrait (abstract, faceless avatar).
	 *
	 * @param int    $seed   Seed.
	 * @param string $path   Output JPEG path.
	 * @param int    $width  Output width.
	 * @param int    $height Output height.
	 * @return bool
	 */
	public function portrait( int $seed, string $path, int $width = 600, int $height = 750 ): bool {
		$this->begin( $seed, $width, $height );

		$backs   = array( array( '#cfd8dc', '#9fb0b8' ), array( '#e8d9c5', '#c8ae8e' ), array( '#d5dccf', '#a8b59c' ), array( '#d9cfdb', '#b0a0b5' ) );
		$skins   = array( '#f1c9a5', '#d9a47c', '#a86f4c', '#e8b894', '#7a4b33' );
		$hairs   = array( '#2b2220', '#5a3b2b', '#a87b4f', '#1d1d1f', '#8c8c8c' );
		$clothes = array( '#26323d', '#3d4a3a', '#5b3d3a', '#1f2a44', '#454545' );

		$back  = $backs[ $this->rand( 0, count( $backs ) - 1 ) ];
		$skin  = $skins[ $this->rand( 0, count( $skins ) - 1 ) ];
		$hair  = $hairs[ $this->rand( 0, count( $hairs ) - 1 ) ];
		$cloth = $clothes[ $this->rand( 0, count( $clothes ) - 1 ) ];
		$long  = 1 === $this->rand( 0, 1 );

		$this->vertical_gradient( 0, 0, $this->w, $this->h, $back[0], $back[1] );

		$cx = (int) ( $this->w / 2 );
		$hy = (int) ( $this->h * 0.40 );
		$hw = (int) ( $this->w * 0.34 );
		$hh = (int) ( $this->h * 0.36 );

		if ( $long ) {
			$this->ellipse( $cx, $hy + (int) ( $hh * 0.30 ), (int) ( $hw * 1.35 ), (int) ( $hh * 1.30 ), $hair );
		}

		// Shoulders and jacket.
		$this->ellipse( $cx, $this->h + (int) ( $this->h * 0.06 ), (int) ( $this->w * 1.05 ), (int) ( $this->h * 0.62 ), $cloth );
		$this->polygon( array( $cx - (int) ( $this->w * 0.09 ), (int) ( $this->h * 0.76 ), $cx + (int) ( $this->w * 0.09 ), (int) ( $this->h * 0.76 ), $cx, (int) ( $this->h * 0.93 ) ), '#f4f1ec' );
		// Neck.
		$this->rect( $cx - (int) ( $hw * 0.22 ), $hy + (int) ( $hh * 0.30 ), $cx + (int) ( $hw * 0.22 ), (int) ( $this->h * 0.80 ), $this->mix( $skin, '#000000', 0.12 ) );
		// Head.
		$this->ellipse( $cx, $hy, $hw, $hh, $skin );
		// Hair cap.
		$this->arc_fill( $cx, $hy - (int) ( $hh * 0.06 ), (int) ( $hw * 1.06 ), (int) ( $hh * 0.98 ), 180, 360, $hair );
		// Side fringe.
		$this->ellipse( $cx - (int) ( $hw * 0.22 ), $hy - (int) ( $hh * 0.18 ), (int) ( $hw * 0.62 ), (int) ( $hh * 0.30 ), $hair );

		return $this->finish( $path );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Scenes
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Two-storey house with pitched roof.
	 *
	 * @param int           $ground Ground line.
	 * @param array<string> $c      Façade palette.
	 * @param array<string> $sky    Sky palette.
	 * @return void
	 */
	private function house( int $ground, array $c, array $sky ): void {
		$this->tree_row( $ground, 0.55 );

		$x0 = (int) ( $this->w * 0.27 );
		$x1 = (int) ( $this->w * 0.73 );
		$y0 = $ground - (int) ( $this->h * 0.30 );

		// Side garage.
		$gx = $x1 - 4;
		$this->rect( $gx, $ground - (int) ( $this->h * 0.15 ), $gx + (int) ( $this->w * 0.12 ), $ground, $c[1] );
		$this->rect( $gx - 6, $ground - (int) ( $this->h * 0.16 ), $gx + (int) ( $this->w * 0.12 ) + 10, $ground - (int) ( $this->h * 0.15 ), $c[2] );
		$this->rect( $gx + 30, $ground - (int) ( $this->h * 0.11 ), $gx + (int) ( $this->w * 0.12 ) - 30, $ground, $this->mix( $c[1], '#000000', 0.18 ) );

		$this->rect( $x0, $y0, $x1, $ground, $c[0] );
		$this->rect( $x0, $y0, $x0 + 24, $ground, $c[1] );

		// Roof.
		$overhang = (int) ( $this->w * 0.03 );
		$this->polygon( array( $x0 - $overhang, $y0 + 6, ( $x0 + $x1 ) / 2, $y0 - (int) ( $this->h * 0.16 ), $x1 + $overhang, $y0 + 6 ), $c[2] );
		$this->polygon( array( $x0 - $overhang, $y0 + 6, ( $x0 + $x1 ) / 2, $y0 - (int) ( $this->h * 0.16 ), ( $x0 + $x1 ) / 2, $y0 + 6 ), $this->mix( $c[2], '#ffffff', 0.10 ) );
		$this->rect( $x1 - (int) ( $this->w * 0.09 ), $y0 - (int) ( $this->h * 0.13 ), $x1 - (int) ( $this->w * 0.06 ), $y0 - (int) ( $this->h * 0.04 ), $c[2] );

		// Windows: two floors.
		$cols  = 3;
		$win_w = (int) ( ( $x1 - $x0 ) * 0.16 );
		$win_h = (int) ( $this->h * 0.085 );
		foreach ( array( $y0 + (int) ( $this->h * 0.04 ), $y0 + (int) ( $this->h * 0.17 ) ) as $row => $wy ) {
			for ( $i = 0; $i < $cols; $i++ ) {
				$wx = $x0 + (int) ( ( $x1 - $x0 ) * ( 0.12 + $i * 0.29 ) );
				if ( 1 === $row && 1 === $i ) {
					// Front door instead of a window.
					$dh = $ground - $wy;
					$this->rect( $wx - 6, $wy - 6, $wx + $win_w + 6, $ground, $c[3] );
					$this->rect( $wx, $wy, $wx + $win_w, $ground, $c[4] );
					$this->ellipse( $wx + $win_w - 18, $wy + (int) ( $dh * 0.55 ), 10, 10, '#e9d9a8' );
					continue;
				}
				$this->window( $wx, $wy, $win_w, $win_h, $c[3], $sky );
			}
		}

		$this->lawn( $ground, $sky );
		$this->path( $ground, $x0 + (int) ( ( $x1 - $x0 ) * 0.45 ), (int) ( ( $x1 - $x0 ) * 0.16 ), '#d8cdbb' );
		$this->bushes( $ground, $x0, $x1 );
		$this->round_tree( (int) ( $this->w * 0.12 ), $ground, 1.1 );
		$this->round_tree( (int) ( $this->w * 0.90 ), $ground, 0.9 );
	}

	/**
	 * Modern villa with pool and palm trees.
	 *
	 * @param int           $ground Ground line.
	 * @param array<string> $c      Façade palette.
	 * @param array<string> $sky    Sky palette.
	 * @return void
	 */
	private function villa( int $ground, array $c, array $sky ): void {
		$this->tree_row( $ground - (int) ( $this->h * 0.04 ), 0.45 );

		$base = $ground - (int) ( $this->h * 0.06 );

		// Upper volume (cantilevered).
		$ux0 = (int) ( $this->w * 0.20 );
		$ux1 = (int) ( $this->w * 0.62 );
		$uy0 = $base - (int) ( $this->h * 0.30 );
		$uy1 = $base - (int) ( $this->h * 0.15 );
		$this->rect( $ux0, $uy0, $ux1, $uy1, $c[0] );
		$this->rect( $ux0 - 20, $uy0 - 14, $ux1 + 30, $uy0, $c[3] );
		$this->glass( $ux0 + (int) ( ( $ux1 - $ux0 ) * 0.35 ), $uy0 + 22, $ux1 - 26, $uy1 - 18, $sky, 4 );
		// Timber cladding.
		$this->rect( $ux0 + 26, $uy0 + 22, $ux0 + (int) ( ( $ux1 - $ux0 ) * 0.30 ), $uy1 - 18, '#a0754f' );
		for ( $x = $ux0 + 36; $x < $ux0 + (int) ( ( $ux1 - $ux0 ) * 0.30 ); $x += 16 ) {
			$this->rect( $x, $uy0 + 22, $x + 3, $uy1 - 18, '#8a6242' );
		}

		// Lower volume.
		$lx0 = (int) ( $this->w * 0.30 );
		$lx1 = (int) ( $this->w * 0.84 );
		$this->rect( $lx0, $uy1, $lx1, $base, $c[1] );
		$this->rect( $lx0 - 10, $uy1 - 12, $lx1 + 24, $uy1, $c[3] );
		$this->glass( $lx0 + 30, $uy1 + 20, $lx1 - 30, $base - 4, $sky, 6 );

		// Terrace and pool.
		$this->rect( 0, $base, $this->w, $base + 14, '#e8e1d5' );
		$this->polygon( array( (int) ( $this->w * 0.10 ), $base + 14, (int) ( $this->w * 0.96 ), $base + 14, $this->w, $this->h, 0, $this->h ), '#efe8dc' );
		$px0 = (int) ( $this->w * 0.16 );
		$px1 = (int) ( $this->w * 0.86 );
		$py0 = $base + (int) ( $this->h * 0.05 );
		$this->polygon( array( $px0 + 40, $py0, $px1 - 10, $py0, $px1 + 40, $this->h - (int) ( $this->h * 0.03 ), $px0 - 30, $this->h - (int) ( $this->h * 0.03 ) ), '#2f8fb5' );
		$this->vertical_gradient_poly_like( $px0 + 40, $py0, $px1 - 10, $this->h - (int) ( $this->h * 0.03 ), '#56b7d6', '#1f6f99' );
		for ( $i = 0; $i < 9; $i++ ) {
			$ry = $py0 + 18 + $i * (int) ( $this->h * 0.012 );
			$rx = $this->rand( $px0 + 80, $px1 - 200 );
			$this->rect( $rx, $ry, $rx + $this->rand( 60, 180 ), $ry + 3, '#a7e1f0', 60 );
		}

		$this->palm( (int) ( $this->w * 0.09 ), $base + 10, 1.15 );
		$this->palm( (int) ( $this->w * 0.93 ), $base + 6, 0.95 );
	}

	/**
	 * Apartment block with balconies.
	 *
	 * @param int           $ground Ground line.
	 * @param array<string> $c      Façade palette.
	 * @param array<string> $sky    Sky palette.
	 * @return void
	 */
	private function apartment( int $ground, array $c, array $sky ): void {
		// Background block.
		$this->rect( (int) ( $this->w * 0.60 ), $ground - (int) ( $this->h * 0.50 ), (int) ( $this->w * 0.90 ), $ground, $this->mix( $c[1], $sky[1], 0.45 ) );
		$step_y = (int) ( $this->h * 0.07 );
		$step_x = (int) ( $this->w * 0.05 );
		for ( $y = $ground - (int) ( $this->h * 0.46 ); $y < $ground - 40; $y += $step_y ) {
			for ( $x = (int) ( $this->w * 0.62 ); $x < (int) ( $this->w * 0.88 ); $x += $step_x ) {
				$this->rect( $x, $y, $x + (int) ( $this->w * 0.03 ), $y + (int) ( $this->h * 0.04 ), $this->mix( $sky[0], $c[1], 0.5 ), 40 );
			}
		}

		$x0     = (int) ( $this->w * 0.22 );
		$x1     = (int) ( $this->w * 0.66 );
		$top    = $ground - (int) ( $this->h * 0.66 );
		$floors = 6;
		$fh     = (int) ( ( $ground - $top ) / ( $floors + 0.4 ) );

		$this->rect( $x0, $top, $x1, $ground, $c[0] );
		$this->rect( $x1 - 30, $top, $x1, $ground, $c[1] );
		$this->rect( $x0 - 10, $top - 16, $x1 + 10, $top, $c[3] );

		for ( $f = 0; $f < $floors; $f++ ) {
			$fy = $top + 24 + $f * $fh;
			for ( $i = 0; $i < 4; $i++ ) {
				$wx = $x0 + 34 + $i * (int) ( ( $x1 - $x0 - 60 ) / 4 );
				$ww = (int) ( ( $x1 - $x0 - 60 ) / 4 ) - 30;
				$this->window( $wx, $fy, $ww, (int) ( $fh * 0.62 ), $c[3], $sky );
			}
			// Balcony slab and glass rail.
			$by = $fy + (int) ( $fh * 0.62 );
			$this->rect( $x0 + 20, $by, $x0 + (int) ( ( $x1 - $x0 ) * 0.5 ), $by + 10, $c[2] );
			$this->rect( $x0 + 20, $by - (int) ( $fh * 0.20 ), $x0 + (int) ( ( $x1 - $x0 ) * 0.5 ), $by, '#cfe3ee', 75 );
			$this->rect( $x0 + 20, $by - (int) ( $fh * 0.20 ), $x0 + (int) ( ( $x1 - $x0 ) * 0.5 ), $by - (int) ( $fh * 0.20 ) + 3, $c[3] );
		}

		// Entrance canopy.
		$ex = (int) ( ( $x0 + $x1 ) / 2 );
		$this->rect( $ex - 70, $ground - (int) ( $fh * 0.70 ), $ex + 70, $ground, $c[3] );
		$this->rect( $ex - 100, $ground - (int) ( $fh * 0.80 ), $ex + 100, $ground - (int) ( $fh * 0.72 ), $c[2] );

		$this->lawn( $ground, $sky );
		$this->round_tree( (int) ( $this->w * 0.10 ), $ground, 1.2 );
		$this->round_tree( (int) ( $this->w * 0.76 ), $ground + 10, 1.0 );
		$this->cypress( (int) ( $this->w * 0.94 ), $ground, 1.0 );
	}

	/**
	 * Glass office tower.
	 *
	 * @param int           $ground Ground line.
	 * @param array<string> $c      Façade palette.
	 * @param array<string> $sky    Sky palette.
	 * @return void
	 */
	private function office( int $ground, array $c, array $sky ): void {
		// Neighbour tower.
		$this->glass( (int) ( $this->w * 0.58 ), $ground - (int) ( $this->h * 0.52 ), (int) ( $this->w * 0.82 ), $ground, $sky, 6, 55 );

		$x0  = (int) ( $this->w * 0.30 );
		$x1  = (int) ( $this->w * 0.62 );
		$top = (int) ( $this->h * 0.08 );
		$this->rect( $x0 - 12, $top - 18, $x1 + 12, $ground, $c[3] );
		$this->glass( $x0, $top, $x1, $ground - 50, $sky, 8 );

		// Floor bands.
		for ( $y = $top + 60; $y < $ground - 60; $y += (int) ( $this->h * 0.05 ) ) {
			$this->rect( $x0, $y, $x1, $y + 5, $this->mix( $c[3], '#ffffff', 0.25 ), 20 );
		}

		// Reflection.
		$this->polygon( array( $x0, $top + 200, $x0 + 160, $top, $x0 + 300, $top, $x0, $top + 380 ), '#ffffff', 95 );

		// Lobby.
		$this->rect( $x0, $ground - 50, $x1, $ground, $this->mix( $c[3], '#000000', 0.2 ) );
		$this->rect( $x0 + 40, $ground - 44, $x1 - 40, $ground, '#e8d8b0', 40 );

		// Plaza.
		$this->rect( 0, $ground, $this->w, $this->h, '#d9d4cc' );
		for ( $x = -$this->w; $x < $this->w * 2; $x += 90 ) {
			$this->line_thick( (int) ( $this->w / 2 ), $ground, $x, $this->h, '#c9c3ba', 2 );
		}
		$this->cypress( (int) ( $this->w * 0.18 ), $ground + 6, 1.2 );
		$this->cypress( (int) ( $this->w * 0.24 ), $ground + 4, 1.0 );
		$this->round_tree( (int) ( $this->w * 0.88 ), $ground + 10, 1.0 );
	}

	/**
	 * Living room furniture.
	 *
	 * @param int $floor_y Floor line.
	 * @return void
	 */
	private function living( int $floor_y ): void {
		$sofas = array( '#7d8f8c', '#b9a58b', '#55606e', '#a46c55' );
		$sofa  = $sofas[ $this->rand( 0, count( $sofas ) - 1 ) ];

		// Art.
		$this->rect( (int) ( $this->w * 0.14 ), (int) ( $this->h * 0.18 ), (int) ( $this->w * 0.40 ), (int) ( $this->h * 0.40 ), '#2d2a28' );
		$this->vertical_gradient( (int) ( $this->w * 0.145 ), (int) ( $this->h * 0.19 ), (int) ( $this->w * 0.395 ), (int) ( $this->h * 0.39 ), '#e7c9a0', '#c97a55' );
		$this->ellipse( (int) ( $this->w * 0.30 ), (int) ( $this->h * 0.27 ), 70, 70, '#f6e6c8' );

		// Rug.
		$this->ellipse( (int) ( $this->w * 0.36 ), (int) ( $this->h * 0.86 ), (int) ( $this->w * 0.62 ), (int) ( $this->h * 0.16 ), '#e9e1d3' );

		// Sofa.
		$sx0 = (int) ( $this->w * 0.08 );
		$sx1 = (int) ( $this->w * 0.52 );
		$this->round_rect( $sx0, $floor_y - (int) ( $this->h * 0.20 ), $sx1, $floor_y - (int) ( $this->h * 0.07 ), 30, $this->mix( $sofa, '#000000', 0.10 ) );
		$this->round_rect( $sx0 - 10, $floor_y - (int) ( $this->h * 0.10 ), $sx1 + 10, $floor_y + (int) ( $this->h * 0.04 ), 24, $sofa );
		$this->round_rect( $sx0 + 30, $floor_y - (int) ( $this->h * 0.13 ), (int) ( ( $sx0 + $sx1 ) / 2 ) - 8, $floor_y - (int) ( $this->h * 0.06 ), 20, $this->mix( $sofa, '#ffffff', 0.15 ) );
		$this->round_rect( (int) ( ( $sx0 + $sx1 ) / 2 ) + 8, $floor_y - (int) ( $this->h * 0.13 ), $sx1 - 30, $floor_y - (int) ( $this->h * 0.06 ), 20, $this->mix( $sofa, '#ffffff', 0.15 ) );
		$this->round_rect( $sx0 + 60, $floor_y - (int) ( $this->h * 0.17 ), $sx0 + 170, $floor_y - (int) ( $this->h * 0.10 ), 18, '#efe3c9' );

		// Coffee table.
		$tx = (int) ( $this->w * 0.36 );
		$ty = $floor_y + (int) ( $this->h * 0.12 );
		$this->ellipse( $tx, $ty, (int) ( $this->w * 0.20 ), (int) ( $this->h * 0.05 ), '#3a302a' );
		$this->rect( $tx - 6, $ty, $tx + 6, $ty + (int) ( $this->h * 0.06 ), '#3a302a' );
		$this->ellipse( $tx - 40, $ty - 12, 40, 20, '#e9e4dc' );

		// Floor lamp.
		$lx = (int) ( $this->w * 0.56 );
		$this->rect( $lx - 3, $floor_y - (int) ( $this->h * 0.36 ), $lx + 3, $floor_y + 8, '#2b2b2b' );
		$this->polygon( array( $lx - 50, $floor_y - (int) ( $this->h * 0.36 ), $lx + 50, $floor_y - (int) ( $this->h * 0.36 ), $lx + 34, $floor_y - (int) ( $this->h * 0.44 ), $lx - 34, $floor_y - (int) ( $this->h * 0.44 ) ), '#f2e3c2' );

		$this->plant( (int) ( $this->w * 0.92 ), $floor_y + 30, 1.2 );
	}

	/**
	 * Kitchen.
	 *
	 * @param int $floor_y Floor line.
	 * @return void
	 */
	private function kitchen( int $floor_y ): void {
		$fronts = array( '#3f4f5c', '#5c6b57', '#e9e5de', '#2d2d2f' );
		$front  = $fronts[ $this->rand( 0, count( $fronts ) - 1 ) ];
		$x0     = (int) ( $this->w * 0.44 );
		$x1     = (int) ( $this->w * 0.98 );

		// Upper cabinets and splashback.
		$this->rect( $x0, (int) ( $this->h * 0.12 ), $x1, (int) ( $this->h * 0.32 ), $front );
		for ( $x = $x0; $x < $x1; $x += (int) ( ( $x1 - $x0 ) / 5 ) ) {
			$this->rect( $x, (int) ( $this->h * 0.12 ), $x + 3, (int) ( $this->h * 0.32 ), $this->mix( $front, '#000000', 0.25 ) );
		}
		$this->rect( $x0, (int) ( $this->h * 0.32 ), $x1, (int) ( $this->h * 0.48 ), '#f4f1ec' );
		for ( $y = (int) ( $this->h * 0.34 ); $y < (int) ( $this->h * 0.48 ); $y += 26 ) {
			$this->rect( $x0, $y, $x1, $y + 2, '#e3ddd4' );
		}

		// Counter and lower cabinets.
		$this->rect( $x0, (int) ( $this->h * 0.48 ), $x1, (int) ( $this->h * 0.50 ), '#d6d2cc' );
		$this->rect( $x0, (int) ( $this->h * 0.50 ), $x1, $floor_y, $front );
		for ( $x = $x0; $x < $x1; $x += (int) ( ( $x1 - $x0 ) / 6 ) ) {
			$this->rect( $x, (int) ( $this->h * 0.50 ), $x + 3, $floor_y, $this->mix( $front, '#000000', 0.25 ) );
			$this->rect( $x + 20, (int) ( $this->h * 0.53 ), $x + 70, (int) ( $this->h * 0.535 ), '#bfb6a8' );
		}

		// Island with stools.
		$ix0 = (int) ( $this->w * 0.30 );
		$ix1 = (int) ( $this->w * 0.80 );
		$iy  = $floor_y - (int) ( $this->h * 0.02 );
		$this->rect( $ix0, $iy, $ix1, $iy + (int) ( $this->h * 0.22 ), $this->mix( $front, '#ffffff', 0.12 ) );
		$this->rect( $ix0 - 20, $iy - 14, $ix1 + 20, $iy, '#ece8e1' );
		foreach ( array( 0.38, 0.52, 0.66 ) as $pos ) {
			$sx = (int) ( $this->w * $pos );
			$this->ellipse( $sx, $iy + (int) ( $this->h * 0.12 ), 90, 26, '#2e2b29' );
			$this->rect( $sx - 4, $iy + (int) ( $this->h * 0.12 ), $sx + 4, $this->h, '#2e2b29' );
		}

		// Pendant lights.
		foreach ( array( 0.42, 0.55, 0.68 ) as $pos ) {
			$px = (int) ( $this->w * $pos );
			$this->rect( $px - 1, 0, $px + 1, (int) ( $this->h * 0.22 ), '#2b2b2b' );
			$this->arc_fill( $px, (int) ( $this->h * 0.25 ), 90, 70, 180, 360, '#2b2b2b' );
			$this->ellipse( $px, (int) ( $this->h * 0.25 ), 50, 14, '#ffe9b0' );
		}

		$this->plant( (int) ( $this->w * 0.06 ), $floor_y + 40, 1.1 );
	}

	/**
	 * Bedroom.
	 *
	 * @param int $floor_y Floor line.
	 * @return void
	 */
	private function bedroom( int $floor_y ): void {
		$heads = array( '#8c9a8f', '#b39b82', '#5f6b7a', '#c7b8a6' );
		$head  = $heads[ $this->rand( 0, count( $heads ) - 1 ) ];
		$bx0   = (int) ( $this->w * 0.10 );
		$bx1   = (int) ( $this->w * 0.54 );

		// Headboard.
		$this->round_rect( $bx0, $floor_y - (int) ( $this->h * 0.32 ), $bx1, $floor_y - (int) ( $this->h * 0.05 ), 26, $head );
		// Mattress and duvet.
		$this->round_rect( $bx0 - 20, $floor_y - (int) ( $this->h * 0.10 ), $bx1 + 20, $floor_y + (int) ( $this->h * 0.10 ), 20, '#fbfaf7' );
		$this->round_rect( $bx0 - 24, $floor_y - (int) ( $this->h * 0.02 ), $bx1 + 24, $floor_y + (int) ( $this->h * 0.13 ), 22, '#e7dfd3' );
		$this->rect( $bx0 - 24, $floor_y + (int) ( $this->h * 0.02 ), $bx1 + 24, $floor_y + (int) ( $this->h * 0.04 ), $this->mix( $head, '#ffffff', 0.3 ) );
		// Pillows.
		$this->round_rect( $bx0 + 20, $floor_y - (int) ( $this->h * 0.15 ), (int) ( ( $bx0 + $bx1 ) / 2 ) - 10, $floor_y - (int) ( $this->h * 0.06 ), 20, '#ffffff' );
		$this->round_rect( (int) ( ( $bx0 + $bx1 ) / 2 ) + 10, $floor_y - (int) ( $this->h * 0.15 ), $bx1 - 20, $floor_y - (int) ( $this->h * 0.06 ), 20, '#ffffff' );

		// Nightstand and lamp.
		$nx = $bx1 + 60;
		$this->rect( $nx, $floor_y - (int) ( $this->h * 0.08 ), $nx + 120, $floor_y + (int) ( $this->h * 0.04 ), '#6b5442' );
		$this->rect( $nx + 56, $floor_y - (int) ( $this->h * 0.16 ), $nx + 64, $floor_y - (int) ( $this->h * 0.08 ), '#2b2b2b' );
		$this->polygon( array( $nx + 20, $floor_y - (int) ( $this->h * 0.16 ), $nx + 100, $floor_y - (int) ( $this->h * 0.16 ), $nx + 86, $floor_y - (int) ( $this->h * 0.23 ), $nx + 34, $floor_y - (int) ( $this->h * 0.23 ) ), '#f2e3c2' );

		// Art above bed.
		$this->rect( $bx0 + 60, (int) ( $this->h * 0.14 ), $bx1 - 60, (int) ( $this->h * 0.30 ), '#ffffff' );
		$this->vertical_gradient( $bx0 + 72, (int) ( $this->h * 0.15 ), $bx1 - 72, (int) ( $this->h * 0.29 ), '#b9c9cf', '#e8d6bf' );

		$this->plant( (int) ( $this->w * 0.94 ), $floor_y + 30, 1.0 );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Elements
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Window with frame, sky reflection and sill.
	 *
	 * @param int           $x     Left.
	 * @param int           $y     Top.
	 * @param int           $w     Width.
	 * @param int           $h     Height.
	 * @param string        $frame Frame colour.
	 * @param array<string> $sky   Sky palette.
	 * @return void
	 */
	private function window( int $x, int $y, int $w, int $h, string $frame, array $sky ): void {
		$this->rect( $x - 6, $y - 6, $x + $w + 6, $y + $h + 6, $frame );
		$lit = 0 === $this->rand( 0, 4 );
		$this->vertical_gradient( $x, $y, $x + $w, $y + $h, $lit ? '#f6d998' : $this->mix( $sky[0], '#1d2a36', 0.35 ), $lit ? '#e9b96a' : $this->mix( $sky[1], '#2a3a48', 0.45 ) );
		$this->polygon( array( $x, $y + (int) ( $h * 0.6 ), $x + (int) ( $w * 0.5 ), $y, $x + (int) ( $w * 0.75 ), $y, $x, $y + $h ), '#ffffff', 105 );
		$this->rect( $x + (int) ( $w / 2 ) - 2, $y, $x + (int) ( $w / 2 ) + 2, $y + $h, $frame );
		$this->rect( $x - 10, $y + $h + 6, $x + $w + 10, $y + $h + 12, $this->mix( $frame, '#ffffff', 0.5 ) );
	}

	/**
	 * Curtain-wall glazing with mullions.
	 *
	 * @param int           $x0    Left.
	 * @param int           $y0    Top.
	 * @param int           $x1    Right.
	 * @param int           $y1    Bottom.
	 * @param array<string> $sky   Sky palette.
	 * @param int           $panes Number of panes.
	 * @param int           $alpha GD alpha (0 opaque – 127 transparent).
	 * @return void
	 */
	private function glass( int $x0, int $y0, int $x1, int $y1, array $sky, int $panes, int $alpha = 0 ): void {
		$this->vertical_gradient( $x0, $y0, $x1, $y1, $this->mix( $sky[0], '#1b2836', 0.25 ), $this->mix( $sky[1], '#33465a', 0.40 ), $alpha );
		$step = max( 1, (int) ( ( $x1 - $x0 ) / $panes ) );
		for ( $x = $x0; $x <= $x1; $x += $step ) {
			$this->rect( $x - 2, $y0, $x + 2, $y1, '#2a2f36', $alpha );
		}
		$this->polygon( array( $x0, $y1 - (int) ( ( $y1 - $y0 ) * 0.3 ), $x0 + (int) ( ( $x1 - $x0 ) * 0.3 ), $y0, $x0 + (int) ( ( $x1 - $x0 ) * 0.42 ), $y0, $x0, $y1 ), '#ffffff', 108 );
	}

	/**
	 * Distant tree line.
	 *
	 * @param int   $ground  Ground line.
	 * @param float $density Density factor.
	 * @return void
	 */
	private function tree_row( int $ground, float $density ): void {
		$count = (int) ( 18 * $density );
		for ( $i = 0; $i < $count; $i++ ) {
			$x = $this->rand( 0, $this->w );
			$r = $this->rand( (int) ( $this->h * 0.05 ), (int) ( $this->h * 0.09 ) );
			$this->ellipse( $x, $ground - (int) ( $r * 0.9 ), $r * 2, (int) ( $r * 2.2 ), '#6f8a63', 40 );
		}
	}

	/**
	 * Rounded deciduous tree.
	 *
	 * @param int   $x     Trunk base x.
	 * @param int   $y     Trunk base y.
	 * @param float $scale Scale.
	 * @return void
	 */
	private function round_tree( int $x, int $y, float $scale ): void {
		$s = $this->h * 0.001 * $scale;
		$this->rect( $x - (int) ( 10 * $s ), $y - (int) ( 170 * $s ), $x + (int) ( 10 * $s ), $y, '#5a4232' );
		$greens = array( '#4f6f45', '#5f8253', '#6f9361' );
		foreach ( array( array( -60, -230, 190 ), array( 50, -250, 170 ), array( 0, -320, 200 ), array( -20, -200, 150 ) ) as $i => $blob ) {
			$this->ellipse( $x + (int) ( $blob[0] * $s ), $y + (int) ( $blob[1] * $s ), (int) ( $blob[2] * $s ), (int) ( $blob[2] * $s * 0.95 ), $greens[ $i % 3 ] );
		}
	}

	/**
	 * Mediterranean cypress.
	 *
	 * @param int   $x     Base x.
	 * @param int   $y     Base y.
	 * @param float $scale Scale.
	 * @return void
	 */
	private function cypress( int $x, int $y, float $scale ): void {
		$s = $this->h * 0.001 * $scale;
		$this->ellipse( $x, $y - (int) ( 200 * $s ), (int) ( 90 * $s ), (int) ( 420 * $s ), '#3e5a3c' );
		$this->ellipse( $x + (int) ( 12 * $s ), $y - (int) ( 220 * $s ), (int) ( 50 * $s ), (int) ( 360 * $s ), '#4c6b48' );
	}

	/**
	 * Palm tree.
	 *
	 * @param int   $x     Base x.
	 * @param int   $y     Base y.
	 * @param float $scale Scale.
	 * @return void
	 */
	private function palm( int $x, int $y, float $scale ): void {
		$s      = $this->h * 0.001 * $scale;
		$height = (int) ( 560 * $s );
		$top_x  = $x + (int) ( 60 * $s );
		$top_y  = $y - $height;

		for ( $i = 0; $i <= 40; $i++ ) {
			$t  = $i / 40;
			$cx = (int) ( $x + ( $top_x - $x ) * $t * $t );
			$cy = (int) ( $y - $height * $t );
			$r  = (int) ( ( 26 - 10 * $t ) * $s );
			$this->ellipse( $cx, $cy, $r, $r, 0 === $i % 4 ? '#7b5f45' : '#8d6f52' );
		}

		$fronds = array( array( -1.0, 0.25 ), array( -0.7, -0.35 ), array( -0.2, -0.6 ), array( 0.4, -0.5 ), array( 0.9, -0.2 ), array( 1.0, 0.3 ) );
		foreach ( $fronds as $i => $dir ) {
			$len = 260 * $s;
			$ex  = (int) ( $top_x + $dir[0] * $len );
			$ey  = (int) ( $top_y + $dir[1] * $len + 60 * $s );
			$mx  = (int) ( ( $top_x + $ex ) / 2 );
			$my  = (int) ( min( $top_y, $ey ) - 40 * $s );
			$this->polygon( array( $top_x, $top_y, $mx, $my - (int) ( 18 * $s ), $ex, $ey, $mx, $my + (int) ( 26 * $s ) ), 0 === $i % 2 ? '#3f6a3a' : '#4f7d47' );
		}
	}

	/**
	 * Potted plant.
	 *
	 * @param int   $x     Base x.
	 * @param int   $y     Base y.
	 * @param float $scale Scale.
	 * @return void
	 */
	private function plant( int $x, int $y, float $scale ): void {
		$s = $this->h * 0.001 * $scale;
		foreach ( array( array( -50, -230, 90, 200 ), array( 40, -260, 80, 220 ), array( 0, -300, 70, 200 ), array( -20, -180, 110, 140 ) ) as $i => $leaf ) {
			$this->ellipse( $x + (int) ( $leaf[0] * $s ), $y + (int) ( $leaf[1] * $s ), (int) ( $leaf[2] * $s ), (int) ( $leaf[3] * $s ), 0 === $i % 2 ? '#4d7048' : '#5f8656' );
		}
		$this->polygon( array( $x - (int) ( 70 * $s ), $y - (int) ( 120 * $s ), $x + (int) ( 70 * $s ), $y - (int) ( 120 * $s ), $x + (int) ( 52 * $s ), $y, $x - (int) ( 52 * $s ), $y ), '#c8b8a2' );
	}

	/**
	 * Lawn strip in front of a building.
	 *
	 * @param int           $ground Ground line.
	 * @param array<string> $sky    Sky palette.
	 * @return void
	 */
	private function lawn( int $ground, array $sky ): void {
		$this->vertical_gradient( 0, $ground, $this->w, $this->h, $this->mix( $sky[2], '#7f9b62', 0.5 ), $this->mix( $sky[2], '#5d7a48', 0.5 ) );
	}

	/**
	 * Perspective path.
	 *
	 * @param int    $ground Ground line.
	 * @param int    $x      Path start x.
	 * @param int    $width  Path width at the door.
	 * @param string $color  Colour.
	 * @return void
	 */
	private function path( int $ground, int $x, int $width, string $color ): void {
		$this->polygon( array( $x, $ground, $x + $width, $ground, $x + (int) ( $width * 2.6 ), $this->h, $x - (int) ( $width * 1.4 ), $this->h ), $color );
	}

	/**
	 * Hedge bushes along a façade.
	 *
	 * @param int $ground Ground line.
	 * @param int $x0     Start.
	 * @param int $x1     End.
	 * @return void
	 */
	private function bushes( int $ground, int $x0, int $x1 ): void {
		$i = 0;
		for ( $x = $x0 - 30; $x < $x1 + 30; $x += 70 ) {
			++$i;
			if ( abs( $x - ( $x0 + ( $x1 - $x0 ) * 0.5 ) ) < 120 ) {
				continue;
			}
			$this->ellipse( $x, $ground - 8, 110, 70, 0 === $i % 2 ? '#56774a' : '#4b6a41' );
		}
	}

	/**
	 * Sun with halo.
	 *
	 * @param string $color Colour.
	 * @return void
	 */
	private function sun( string $color ): void {
		$x = $this->rand( (int) ( $this->w * 0.65 ), (int) ( $this->w * 0.88 ) );
		$y = $this->rand( (int) ( $this->h * 0.12 ), (int) ( $this->h * 0.28 ) );
		for ( $r = 6; $r >= 1; $r-- ) {
			$this->ellipse( $x, $y, 60 * $r, 60 * $r, $color, 112 );
		}
		$this->ellipse( $x, $y, 110, 110, $color );
	}

	/**
	 * Soft clouds.
	 *
	 * @return void
	 */
	private function clouds(): void {
		$count = $this->rand( 2, 4 );
		for ( $i = 0; $i < $count; $i++ ) {
			$cx = $this->rand( 0, $this->w );
			$cy = $this->rand( (int) ( $this->h * 0.06 ), (int) ( $this->h * 0.30 ) );
			for ( $j = 0; $j < 5; $j++ ) {
				$this->ellipse( $cx + $this->rand( -160, 160 ), $cy + $this->rand( -20, 20 ), $this->rand( 140, 300 ), $this->rand( 50, 90 ), '#ffffff', 85 );
			}
		}
	}

	/**
	 * Rolling hills on the horizon.
	 *
	 * @param int    $horizon   Horizon line.
	 * @param string $color     Colour.
	 * @param float  $amplitude Height factor.
	 * @return void
	 */
	private function hills( int $horizon, string $color, float $amplitude ): void {
		$points = array( 0, $horizon + 2 );
		$phase  = $this->rand( 0, 628 ) / 100;
		for ( $x = 0; $x <= $this->w; $x += 40 ) {
			$points[] = $x;
			$points[] = (int) ( $horizon - $this->h * $amplitude * ( 0.5 + 0.5 * sin( $x / $this->w * 5 + $phase ) ) );
		}
		$points[] = $this->w;
		$points[] = $horizon + 2;
		$this->polygon( $points, $color );
	}

	/**
	 * Hills seen through a window.
	 *
	 * @param int $x Window left.
	 * @param int $y Window top.
	 * @param int $w Window width.
	 * @param int $h Window height.
	 * @return void
	 */
	private function hills_in_rect( int $x, int $y, int $w, int $h ): void {
		$points = array( $x, $y + $h );
		for ( $i = 0; $i <= 10; $i++ ) {
			$points[] = $x + (int) ( $w * $i / 10 );
			$points[] = $y + (int) ( $h * ( 0.62 + 0.08 * sin( $i ) ) );
		}
		$points[] = $x + $w;
		$points[] = $y + $h;
		$this->polygon( $points, '#9db58e' );
	}

	/**
	 * Wooden floor with planks converging to a vanishing point.
	 *
	 * @param int           $floor_y Floor line.
	 * @param array<string> $colors  Light/dark wood.
	 * @return void
	 */
	private function floor_planks( int $floor_y, array $colors ): void {
		$this->vertical_gradient( 0, $floor_y, $this->w, $this->h, $colors[0], $colors[1] );
		$this->rect( 0, $floor_y - 8, $this->w, $floor_y, '#ffffff' );
		$vx = (int) ( $this->w / 2 );
		for ( $x = -$this->w; $x <= $this->w * 2; $x += 120 ) {
			$top_x = (int) ( $vx + ( $x - $vx ) * 0.35 );
			$this->line_thick( $top_x, $floor_y, $x, $this->h, $this->mix( $colors[1], '#000000', 0.12 ), 2 );
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Drawing primitives
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Starts a new canvas.
	 *
	 * @param int $seed   Seed.
	 * @param int $width  Output width.
	 * @param int $height Output height.
	 * @return void
	 */
	private function begin( int $seed, int $width, int $height ): void {
		$this->state = max( 1, $seed * 7919 % 2147483647 );
		$this->w     = (int) ( $width * self::SUPERSAMPLE );
		$this->h     = (int) ( $height * self::SUPERSAMPLE );
		$this->img   = imagecreatetruecolor( $this->w, $this->h );
		imagealphablending( $this->img, true );
	}

	/**
	 * Downsamples, saves as progressive JPEG and frees memory.
	 *
	 * @param string $path Output path.
	 * @return bool
	 */
	private function finish( string $path ): bool {
		$out_w = (int) round( $this->w / self::SUPERSAMPLE );
		$out_h = (int) round( $this->h / self::SUPERSAMPLE );
		$out   = imagecreatetruecolor( $out_w, $out_h );

		imagecopyresampled( $out, $this->img, 0, 0, 0, 0, $out_w, $out_h, $this->w, $this->h );
		imageinterlace( $out, true );

		$ok = imagejpeg( $out, $path, 82 );

		unset( $out );
		$this->img = null;

		return $ok;
	}

	/**
	 * Deterministic integer in [min, max] (Park–Miller LCG).
	 *
	 * @param int $min Min.
	 * @param int $max Max.
	 * @return int
	 */
	private function rand( int $min, int $max ): int {
		$this->state = ( $this->state * 48271 ) % 2147483647;

		if ( $max <= $min ) {
			return $min;
		}

		return $min + $this->state % ( $max - $min + 1 );
	}

	/**
	 * Allocates a colour.
	 *
	 * @param string $hex   #rrggbb.
	 * @param int    $alpha 0 (opaque) – 127 (transparent).
	 * @return int
	 */
	private function color( string $hex, int $alpha = 0 ): int {
		$hex = ltrim( $hex, '#' );

		return imagecolorallocatealpha( $this->img, hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ), $alpha );
	}

	/**
	 * Mixes two hex colours.
	 *
	 * @param string $a Colour A.
	 * @param string $b Colour B.
	 * @param float  $t 0 = A, 1 = B.
	 * @return string
	 */
	private function mix( string $a, string $b, float $t ): string {
		$a   = ltrim( $a, '#' );
		$b   = ltrim( $b, '#' );
		$out = '#';
		for ( $i = 0; $i < 3; $i++ ) {
			$ca   = hexdec( substr( $a, $i * 2, 2 ) );
			$cb   = hexdec( substr( $b, $i * 2, 2 ) );
			$out .= str_pad( dechex( (int) round( $ca + ( $cb - $ca ) * $t ) ), 2, '0', STR_PAD_LEFT );
		}

		return $out;
	}

	/**
	 * Filled rectangle.
	 *
	 * @param int    $x0    Left.
	 * @param int    $y0    Top.
	 * @param int    $x1    Right.
	 * @param int    $y1    Bottom.
	 * @param string $hex   Colour.
	 * @param int    $alpha Alpha.
	 * @return void
	 */
	private function rect( int $x0, int $y0, int $x1, int $y1, string $hex, int $alpha = 0 ): void {
		imagefilledrectangle( $this->img, $x0, $y0, $x1, $y1, $this->color( $hex, $alpha ) );
	}

	/**
	 * Rounded rectangle.
	 *
	 * @param int    $x0  Left.
	 * @param int    $y0  Top.
	 * @param int    $x1  Right.
	 * @param int    $y1  Bottom.
	 * @param int    $r   Radius.
	 * @param string $hex Colour.
	 * @return void
	 */
	private function round_rect( int $x0, int $y0, int $x1, int $y1, int $r, string $hex ): void {
		$c = $this->color( $hex );
		imagefilledrectangle( $this->img, $x0 + $r, $y0, $x1 - $r, $y1, $c );
		imagefilledrectangle( $this->img, $x0, $y0 + $r, $x1, $y1 - $r, $c );
		foreach ( array( array( $x0 + $r, $y0 + $r ), array( $x1 - $r, $y0 + $r ), array( $x0 + $r, $y1 - $r ), array( $x1 - $r, $y1 - $r ) ) as $corner ) {
			imagefilledellipse( $this->img, $corner[0], $corner[1], $r * 2, $r * 2, $c );
		}
	}

	/**
	 * Filled ellipse.
	 *
	 * @param int    $cx    Centre x.
	 * @param int    $cy    Centre y.
	 * @param int    $w     Width.
	 * @param int    $h     Height.
	 * @param string $hex   Colour.
	 * @param int    $alpha Alpha.
	 * @return void
	 */
	private function ellipse( int $cx, int $cy, int $w, int $h, string $hex, int $alpha = 0 ): void {
		imagefilledellipse( $this->img, $cx, $cy, max( 1, $w ), max( 1, $h ), $this->color( $hex, $alpha ) );
	}

	/**
	 * Filled arc (pie).
	 *
	 * @param int    $cx    Centre x.
	 * @param int    $cy    Centre y.
	 * @param int    $w     Width.
	 * @param int    $h     Height.
	 * @param int    $start Start angle.
	 * @param int    $end   End angle.
	 * @param string $hex   Colour.
	 * @return void
	 */
	private function arc_fill( int $cx, int $cy, int $w, int $h, int $start, int $end, string $hex ): void {
		imagefilledarc( $this->img, $cx, $cy, $w, $h, $start, $end, $this->color( $hex ), IMG_ARC_PIE );
	}

	/**
	 * Filled polygon.
	 *
	 * @param array<int|float> $points Flat list of x, y.
	 * @param string           $hex    Colour.
	 * @param int              $alpha  Alpha.
	 * @return void
	 */
	private function polygon( array $points, string $hex, int $alpha = 0 ): void {
		imagefilledpolygon( $this->img, array_map( 'intval', $points ), $this->color( $hex, $alpha ) );
	}

	/**
	 * Thick line.
	 *
	 * @param int    $x0        Start x.
	 * @param int    $y0        Start y.
	 * @param int    $x1        End x.
	 * @param int    $y1        End y.
	 * @param string $hex       Colour.
	 * @param int    $thickness Thickness.
	 * @return void
	 */
	private function line_thick( int $x0, int $y0, int $x1, int $y1, string $hex, int $thickness ): void {
		imagesetthickness( $this->img, $thickness );
		imageline( $this->img, $x0, $y0, $x1, $y1, $this->color( $hex ) );
		imagesetthickness( $this->img, 1 );
	}

	/**
	 * Vertical gradient in a rectangle.
	 *
	 * @param int    $x0    Left.
	 * @param int    $y0    Top.
	 * @param int    $x1    Right.
	 * @param int    $y1    Bottom.
	 * @param string $top   Top colour.
	 * @param string $bot   Bottom colour.
	 * @param int    $alpha Alpha.
	 * @return void
	 */
	private function vertical_gradient( int $x0, int $y0, int $x1, int $y1, string $top, string $bot, int $alpha = 0 ): void {
		$height = max( 1, $y1 - $y0 );
		for ( $y = $y0; $y < $y1; $y += 2 ) {
			$this->rect( $x0, $y, $x1, $y + 1, $this->mix( $top, $bot, ( $y - $y0 ) / $height ), $alpha );
		}
	}

	/**
	 * Gradient over a trapezoid-like area (pool water), drawn as shrinking bands.
	 *
	 * @param int    $x0  Left.
	 * @param int    $y0  Top.
	 * @param int    $x1  Right.
	 * @param int    $y1  Bottom.
	 * @param string $top Top colour.
	 * @param string $bot Bottom colour.
	 * @return void
	 */
	private function vertical_gradient_poly_like( int $x0, int $y0, int $x1, int $y1, string $top, string $bot ): void {
		$height = max( 1, $y1 - $y0 );
		for ( $y = $y0; $y < $y1; $y += 2 ) {
			$t     = ( $y - $y0 ) / $height;
			$left  = (int) ( $x0 - 70 * $t );
			$right = (int) ( $x1 + 50 * $t );
			$this->rect( $left, $y, $right, $y + 1, $this->mix( $top, $bot, $t ) );
		}
	}
}
