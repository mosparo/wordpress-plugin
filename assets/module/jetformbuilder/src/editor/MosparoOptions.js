const {
	__,
} = wp.i18n;
let {
	Tip,
	Button,
} = wp.components;

function MosparoOptions() {
	return <>
		<div style={{ marginBottom: '1rem' }}>
			<Tip>
				{ __('You can configure the mosparo connection in the mosparo settings.', 'mosparo-integration') }
			</Tip>
		</div>
		<Button
			size={ 'default' }
			variant={ 'secondary' }
			href={ jfbMosparoIntegration.settingsUrl }
			target={ '_blank' }
		>
			{ __('Configure mosparo Integration', 'mosparo-integration') }
		</Button>
	</>;
}

export default MosparoOptions;