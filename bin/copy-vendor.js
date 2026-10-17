/**
 * Copies self-hosted fonts and Leaflet from node_modules into the theme.
 * Run with `npm run vendor`. The copied files are committed so the theme works without a build step.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const root = path.resolve( __dirname, '..' );
const theme = path.join( root, 'themes/keystone/assets' );

const copies = [
	[ '@fontsource-variable/manrope/files/manrope-latin-wght-normal.woff2', 'fonts/manrope-latin.woff2' ],
	[ '@fontsource/instrument-serif/files/instrument-serif-latin-400-italic.woff2', 'fonts/instrument-serif-latin-italic.woff2' ],
	[ 'leaflet/dist/leaflet.js', 'vendor/leaflet/leaflet.js' ],
	[ 'leaflet/dist/leaflet.css', 'vendor/leaflet/leaflet.css' ],
	[ 'leaflet/LICENSE', 'vendor/leaflet/LICENSE' ],
	...[ 'layers.png', 'layers-2x.png', 'marker-icon.png', 'marker-icon-2x.png', 'marker-shadow.png' ].map( ( image ) => [
		`leaflet/dist/images/${ image }`,
		`vendor/leaflet/images/${ image }`,
	] ),
];

for ( const [ from, to ] of copies ) {
	const target = path.join( theme, to );
	fs.mkdirSync( path.dirname( target ), { recursive: true } );
	fs.copyFileSync( path.join( root, 'node_modules', from ), target );
	console.log( `copied ${ to }` );
}
