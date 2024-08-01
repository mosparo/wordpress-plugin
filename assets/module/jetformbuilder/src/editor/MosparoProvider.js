import MosparoOptions from './MosparoOptions';
import MosparoBlockEdit from './MosparoBlockEdit';

const {
	CaptchaOptions,
	CaptchaBlockEdit,
	CaptchaBlockTip,
} = JetFBComponents;

export default function MosparoProvider() {
	return <>
		<CaptchaOptions provider={ 'mosparo' }>
			{ props => {
				return <>
					<div style={{ marginBottom: '1.5em' }}>
						<MosparoOptions {...props} />
					</div>
					<CaptchaBlockTip/>
				</>;
			} }
		</CaptchaOptions>
		<CaptchaBlockEdit provider={ 'mosparo' }>
			{ props => <MosparoBlockEdit { ...props } /> }
		</CaptchaBlockEdit>
	</>;
}