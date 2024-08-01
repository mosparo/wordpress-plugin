import MosparoOptions from './MosparoOptions';
import preview from './preview';

const {
	InspectorControls,
	useBlockProps,
} = wp.blockEditor;

function MosparoBlockEdit({ isSelected, attributes }) {
	const blockProps = useBlockProps();

	if (attributes.isPreview) {
		return <div style={{ width: '100%' }}>
			{ preview }
		</div>;
	}

	return <>
		<div { ...blockProps }>
			{ isSelected
				? <div style={{ padding: '20px' }}>
					<MosparoOptions/>
				</div>
				: <div style={{ width: '100%' }}>{preview}</div> }
		</div>
		<InspectorControls>
			<div style={{ padding: '20px' }}>
				<MosparoOptions/>
			</div>
		</InspectorControls>
	</>;
}

export default MosparoBlockEdit;