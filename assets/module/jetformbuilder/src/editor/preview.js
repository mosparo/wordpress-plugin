const preview = (
	<svg width="400" height="80" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 400 80">
		<defs>
			<linearGradient id="borderGradient" x1="80" y1="160" x2="320" y2="-80" gradientUnits="userSpaceOnUse">
			<stop offset="0" stopColor="#462982"/>
			<stop offset="1" stopColor="#2d2d7c"/>
			</linearGradient>
		</defs>
		<g>
			<g>
				<g>
					<rect fill="#fff" width="400" height="80"/>
					<path fill="url(#borderGradient)" d="M395,5v70H5V5h390M400,0H0v80h400V0h0Z"/>
				</g>
				<g>
					<path fill="#1e1e1c" d="M116.7,45.4c2.6-9.8-3.1-19.8-12.9-22.4-6.2-1.7-12.7,0-17.4,4.4l-8.8-1.2,4,8.7c-3,9.4,1.8,19.4,11.1,22.8l1-2.8c-7.9-2.9-12-11.7-9.1-19.6l.2-.6-2.2-4.8,4.8.6.5-.5c5.8-6.1,15.5-6.3,21.6-.5,4,3.8,5.6,9.5,4.2,14.9l-22.1-8.1-.5,1.4c-2.6,6.8.8,14.5,7.6,17.2,6.5,2.5,13.8-.4,16.8-6.7l5.2,1.9,1-2.8-5.2-1.9ZM100.2,51.9c-4.8-1.8-7.6-6.8-6.5-11.8l19.2,7c-2.4,4.5-7.8,6.6-12.7,4.8Z"/>
					<circle fill="#1e1e1c" cx="96.5" cy="31.4" r="2.8"/>
					<path fill="#1e1e1c" d="M163.6,26.5c-3.5,0-6.8,1.7-9,4.4-1.8-2.7-4.8-4.4-8.1-4.4-2.8,0-5.5,1.1-7.7,2.9v-2.7h-3v24.1h3v-15.5c0-3.3,4.2-6,7.6-6s6.4,2.7,6.4,6v15.5h3v-15.5c0-3.3,4.2-6,7.6-6s6.4,2.7,6.4,6v15.5h3v-15.5c0-4.9-4.2-8.9-9.4-8.9Z"/>
					<path fill="#1e1e1c" d="M189.4,26.5c-6.9,0-12.4,5.6-12.4,12.4s5.6,12.4,12.4,12.4c6.9,0,12.4-5.6,12.4-12.4h0c0-6.9-5.6-12.4-12.4-12.4ZM189.4,48.3c-5.2,0-9.4-4.2-9.4-9.4,0-5.2,4.2-9.4,9.4-9.4,5.2,0,9.4,4.2,9.4,9.4,0,5.2-4.2,9.4-9.4,9.4Z"/>
					<path fill="#1e1e1c" d="M309.9,26.5c-6.9,0-12.4,5.6-12.4,12.4,0,6.9,5.6,12.4,12.4,12.4,6.9,0,12.4-5.6,12.4-12.4h0c0-6.9-5.6-12.4-12.4-12.4ZM309.9,48.3c-5.2,0-9.4-4.2-9.4-9.4s4.2-9.4,9.4-9.4c5.2,0,9.4,4.2,9.4,9.4,0,5.2-4.2,9.4-9.4,9.4Z"/>
					<path fill="#1e1e1c" d="M238.3,26.5c-3.6,0-7.1,1.6-9.4,4.3v-4h-3v30.9h3v-10.7c4.5,5.2,12.3,5.8,17.5,1.3,5.2-4.5,5.8-12.3,1.3-17.5-2.4-2.7-5.8-4.3-9.4-4.3ZM238.3,48.3c-5.2,0-9.4-4.2-9.4-9.4,0-5.2,4.2-9.4,9.4-9.4,5.2,0,9.4,4.2,9.4,9.4,0,5.2-4.2,9.4-9.4,9.4Z"/>
					<path fill="#1e1e1c" d="M276.1,30.8c-4.4-5.2-12.3-5.8-17.5-1.4-5.2,4.4-5.8,12.3-1.4,17.5,4.4,5.2,12.3,5.8,17.5,1.4.5-.4,1-.9,1.4-1.4v4h3v-24.1h-3v4ZM266.7,48.3c-5.2,0-9.4-4.2-9.4-9.4,0-5.2,4.2-9.4,9.4-9.4,5.2,0,9.4,4.2,9.4,9.4,0,5.2-4.2,9.4-9.4,9.4Z"/>
					<path fill="#1e1e1c" d="M287.9,29.5v-2.7h-3v24.1h3v-14.5c0-3.7,3-6.7,6.7-6.7h2.7v-3h-2.7c-2.5,0-4.9,1-6.7,2.7Z"/>
					<path fill="#1e1e1c" d="M214,37c-4.3-1.7-5.7-2.5-5.7-4.2s1.3-3.3,4.7-3.3,4.9,1.2,4.9,1.2l2.1-2.1c-.2-.2-2.2-2.1-7-2.1s-7.7,3.2-7.7,6.3,3.7,5.4,7.6,7c3.8,1.5,4.5,3.1,4.5,5s-1.3,3.3-4.7,3.3c-2.1,0-4.2-.5-6.1-1.6l-1.7,2.5c2.3,1.4,5,2.2,7.7,2.1,5,0,7.7-3.2,7.7-6.3,0-3.6-2-6-6.4-7.8Z"/>
				</g>
			</g>
		</g>
	</svg>
);

export default preview;