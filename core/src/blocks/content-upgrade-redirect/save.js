import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import classnames from 'classnames';

export default function Save() {
	const { className, ...blockProps } = useBlockProps.save();
	return (
		<div
			{ ...blockProps }
			className={ classnames(
				'restrict-content-pro-content-upgrade-redirect__inner-content',
				className
			) }
		>
			<InnerBlocks.Content />
		</div>
	);
}
