/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Block Dependencies
 */
import Edit from './edit';
import Save from './save';
import metadata from './block.json';
import './index.scss';
import './style.scss';

registerBlockType( 'restrict-content-pro/content-upgrade-redirect', {
	...metadata,
	description: __(
		'Link to the Registration form, and then redirect the registration form to the specified page.',
		'rcp'
	),
	title: __( 'Content Upgrade Redirect', 'rcp' ),
	edit: Edit,
	save: Save,
} );
