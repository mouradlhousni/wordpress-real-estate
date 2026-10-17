/**
 * "Property Search Bar" block (dynamic: rendered by render.php).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

function Edit() {
	return (
		<div { ...useBlockProps() }>
			<Disabled>
				<ServerSideRender block={ metadata.name } />
			</Disabled>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
