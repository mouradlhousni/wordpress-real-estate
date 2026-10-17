/**
 * "Featured Properties" block (dynamic: rendered by render.php).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	Disabled,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

function Edit( { attributes, setAttributes } ) {
	const { count, city, status, featuredOnly } = attributes;
	const blockProps = useBlockProps();

	const cities = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', 'property_city', {
				per_page: 100,
				hide_empty: true,
				_fields: 'id,name,slug',
			} ),
		[]
	);

	const cityOptions = [
		{ label: __( 'All cities', 'keystone' ), value: '' },
		...( cities || [] ).map( ( term ) => ( {
			label: term.name,
			value: term.slug,
		} ) ),
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Listings', 'keystone' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Number of properties', 'keystone' ) }
						value={ count }
						onChange={ ( value ) =>
							setAttributes( { count: value } )
						}
						min={ 1 }
						max={ 12 }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'City', 'keystone' ) }
						value={ city }
						options={ cityOptions }
						onChange={ ( value ) =>
							setAttributes( { city: value } )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Status', 'keystone' ) }
						value={ status }
						options={ [
							{
								label: __( 'Any status', 'keystone' ),
								value: '',
							},
							{
								label: __( 'For Sale', 'keystone' ),
								value: 'for-sale',
							},
							{
								label: __( 'For Rent', 'keystone' ),
								value: 'for-rent',
							},
							{ label: __( 'Sold', 'keystone' ), value: 'sold' },
						] }
						onChange={ ( value ) =>
							setAttributes( { status: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Featured properties only', 'keystone' ) }
						checked={ featuredOnly }
						onChange={ ( value ) =>
							setAttributes( { featuredOnly: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<Disabled>
					<ServerSideRender
						block={ metadata.name }
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
