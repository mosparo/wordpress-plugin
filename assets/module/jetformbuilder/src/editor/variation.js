const {
	__,
} = wp.i18n;

const variation = {
	name: 'mosparo',
	title: __('mosparo', 'mosparo-integration'),
	isActive: (blockAttributes, variationAttributes) => (
		blockAttributes.provider === variationAttributes.provider
	),
	description: __('Protect your form with mosparo.', 'mosparo-integration'),
	icon: <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path fill="currentColor"
			  d="M55.2,39.5c3.6-13.5-4.3-27.4-17.7-31.1-8.4-2.3-17.5,0-23.8,6.1l-12-1.6,5.5,12c-4.2,13,2.5,26.9,15.2,31.7l1.4-3.9c-10.9-4-16.5-16.2-12.5-27.2l.3-.8-3.1-6.7,6.6.9.7-.8c8-8.5,21.3-8.8,29.6-.7,5.5,5.3,7.7,13.2,5.8,20.6l-30.3-11.2-.7,2c-3.6,9.5,1,20.2,10.4,23.8,8.9,3.5,18.9-.6,23-9.2l7.1,2.6,1.4-3.9-7.1-2.6ZM32.6,48.5c-6.6-2.4-10.4-9.4-9-16.3l26.3,9.7c-3.4,6.3-10.8,9.1-17.4,6.6Z"/>
		<path fill="currentColor" d="M29.7,17c1.7-1.3,4.1-.9,5.3.8s.9,4.1-.8,5.4-4.1.9-5.3-.8-.9-4.1.8-5.4Z"/>
	</svg>,
	scope: ['block', 'inserter'],
	attributes: {
		'provider': 'mosparo',
	},
};

export default variation;