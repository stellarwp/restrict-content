/**
 * WordPress Dependencies
 */
import {useEffect} from '@wordpress/element';
import {InnerBlocks, InspectorControls, URLInput, useBlockProps,} from '@wordpress/block-editor';
import {addQueryArgs} from '@wordpress/url';
import {PanelBody, PanelRow} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import {useDispatch, useSelect} from '@wordpress/data';

/**
 * External Dependencies
 */
import classnames from 'classnames';
import {isEmpty} from 'lodash';
import {usePrevious} from "@wordpress/compose";

const ALLOWED_BLOCKS = [ 'core/button', 'core/paragraph' ];
const TEMPLATE = [
	[ 'core/button', { align: 'center', placeholder: 'Register' } ],
	[
		'core/paragraph',
		{
			placeholder: __( 'Already a Member? Example Text', 'rcp' ),
			className: 'restrict-content-pro-content-login-link',
			align: 'center',
		},
	],
];

export default function Edit( props ) {
	const { className, ...blockProps } = useBlockProps();
	const {
		attributes: { redirectUrl, registrationUrl, loginUrl },
		setAttributes,
		clientId,
	} = props;

	const { blockOrder, button, paragraph } = useSelect(
		( select ) => {
			const childBlocks = select( 'core/block-editor' ).getBlock(
				clientId
			).innerBlocks;

			return {
				blockOrder: select( 'core/block-editor' ).getBlockOrder(
					clientId
				),
				button: select( 'core/block-editor' ).getBlock(
					childBlocks?.[ 0 ]?.clientId
				),
				paragraph: select( 'core/block-editor' ).getBlock(
					childBlocks?.[ 1 ]?.clientId
				),
			};
		},
		[ clientId ]
	);

	// const childBlocks = parentBlock.innerBlocks;
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );
	const prevRegistrationUrl = usePrevious( registrationUrl );
	const prevRedirectUrl = usePrevious( redirectUrl );

	useEffect( () => {
		// Setup the default registrationUrl from the Restrict Content Pro settings
		if ( registrationUrl === undefined ) {
			setAttributes( {
				registrationUrl: rcp_default_registration_page,
			} );
		}

		// Setup the default loginUrl from the Restrict Content Pro Settings
		if ( loginUrl === undefined ) {
			setAttributes( {
				loginUrl: rcp_default_account_page,
			} );
		}

		if ( redirectUrl === undefined ) {
			setAttributes( {
				redirectUrl: '',
			} );
		}

		if ( ! isEmpty( button ) ) {
			const buttonAttributes = {};

			/**
			 * Set the initial text to the placeholder so that the button does not show up
			 * without text if the text is not changed by the user
			 */
			if ( button.attributes.text === undefined ) {
				buttonAttributes.text = button.attributes.placeholder;
			}

			/**
			 * First iteration  of the block, set the url on the button
			 */
			if ( button.attributes.url === undefined ) {
				buttonAttributes.url = registrationUrl;
			}

			/**
			 * The redirectUrl has changed, so we need to update the button's url.
			 */
			if ( redirectUrl !== prevRedirectUrl ) {
				buttonAttributes.url = addQueryArgs( registrationUrl, {
					rcp_redirect: redirectUrl,
				} );
			}

			/**
			 * The registrationUrl has changed, so we need to update the button's url.
			 */
			if ( registrationUrl !== prevRegistrationUrl ) {
				buttonAttributes.url = addQueryArgs( registrationUrl, {
					rcp_redirect: redirectUrl,
				} );
			}

			/**
			 * Update the button if we have updates to make
			 */
			if ( ! isEmpty( buttonAttributes ) ) {
				updateBlockAttributes( button.clientId, buttonAttributes );
			}
		}

		if ( ! isEmpty( paragraph ) ) {
			const paragraphAttributes = {};
			const initialParagraphContent = paragraph.attributes.content
				? paragraph.attributes.content
				: '';
			const splitContent = initialParagraphContent.split( /<br>/ );
			const builtHref = addQueryArgs( loginUrl, {
				rcp_redirect: redirectUrl,
			} );

			if ( initialParagraphContent.length > 0 ) {
				const paragraphHref = initialParagraphContent.match(
					/href="([^"]*)/
				);

				if ( paragraphHref !== null ) {
					const parsedHref = paragraphHref[ 1 ];
					if ( parsedHref !== loginUrl ) {
						if ( parsedHref !== null && parsedHref !== loginUrl ) {
							setAttributes( {
								loginUrl: parsedHref,
							} );
						}
					}
				}
			}

			// Paragraph content does not contain any breaks
			if (
				splitContent.length === 1 &&
				initialParagraphContent.length > 0
			) {
				// Need to get the initial content in there if the paragraph is completely blank.
				if ( splitContent[ 0 ] === '' ) {
					paragraphAttributes.content =
						'' +
						"<a href='" +
						builtHref +
						"'>" +
						paragraph.attributes.placeholder +
						'</a>';
				}
				// Whoops, looks like the href is missing
				else if (
					splitContent[ 0 ].search(
						"<a href='" + builtHref + "'>"
					) === -1
				) {
					if (
						splitContent[ 0 ].replace( /(<([^>]+)>)/gi, '' ) !==
						paragraph.attributes.placeholder
					) {
						paragraphAttributes.content =
							"<a href='" +
							builtHref +
							"'>" +
							paragraph.attributes.content.replace(
								/(<([^>]+)>)/gi,
								''
							) +
							'</a>';
					} else {
						paragraphAttributes.content =
							"<a href='" +
							builtHref +
							"'>" +
							paragraph.attributes.placeholder +
							'</a>';
					}
				}
			}

			if ( ! isEmpty( paragraphAttributes ) ) {
				updateBlockAttributes(
					paragraph.clientId,
					paragraphAttributes
				);
			}
		}
	}, [
		blockOrder,
		registrationUrl,
		prevRegistrationUrl,
		redirectUrl,
		loginUrl,
		button,
		paragraph,
	] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Redirect Settings', 'rcp' ) }>
					<PanelRow>
						<URLInput
							label={ __( 'Registration Button URL', 'rcp' ) }
							className={ 'rcp-content-upgrade-redirect-url' }
							value={ registrationUrl }
							onChange={ ( newRegistrationUrl ) =>
								setAttributes( {
									registrationUrl: newRegistrationUrl,
								} )
							}
						/>
					</PanelRow>

					<PanelRow>
						<URLInput
							label={ __( 'Login Text URL', 'rcp' ) }
							className={ 'rcp-content-upgrade-redirect-url' }
							value={ loginUrl }
							onChange={ ( newLoginUrl ) =>
								setAttributes( {
									loginUrl: newLoginUrl,
								} )
							}
						/>
					</PanelRow>

					<PanelRow>
						<URLInput
							label={ __( 'Redirect Destination URL', 'rcp' ) }
							className={ 'rcp-content-upgrade-redirect-url' }
							value={ redirectUrl }
							onChange={ ( newRedirectUrl ) =>
								setAttributes( {
									redirectUrl: newRedirectUrl,
								} )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<div
				{ ...blockProps }
				className={ classnames(
					'restrict-content-pro-content-upgrade-redirect__inner-content',
					className
				) }
			>
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
					templateLock="all"
				/>
			</div>
		</>
	);
}
