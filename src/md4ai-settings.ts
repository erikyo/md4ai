import { __ } from '@wordpress/i18n';
import {
	getAvailableModels,
	getAvailableServices,
	waitForAiServices,
} from './md4ai-services';

/**
 * Initializes the settings page functionality.
 */
export function initSettings() {
	const serviceSelect = document.getElementById(
		'md4ai-default-service'
	) as HTMLSelectElement;
	const modelSelect = document.getElementById(
		'md4ai-default-model'
	) as HTMLSelectElement;

	if ( ! serviceSelect || ! modelSelect ) {
		return;
	}

	const selectedService = serviceSelect.dataset.selected || '';
	const selectedModel = modelSelect.dataset.selected || '';

	function updateModels( serviceIdentifier: string ) {
		if ( ! serviceIdentifier ) {
			modelSelect.innerHTML = `<option value="">${ __(
				'Select service first',
				'md4ai'
			) }</option>`;
			modelSelect.disabled = true;
			return;
		}

		const models = getAvailableModels( serviceIdentifier );
		modelSelect.innerHTML = `<option value="">${ __(
			'Select Model',
			'md4ai'
		) }</option>`;
		models.forEach( ( model ) => {
			const option = document.createElement( 'option' );
			option.value = model.identifier;
			option.textContent = model.label;
			modelSelect.appendChild( option );
		} );
		modelSelect.disabled = false;

		// Select saved model or first model by default
		if (
			selectedModel &&
			models.some( ( m ) => m.identifier === selectedModel )
		) {
			modelSelect.value = selectedModel;
		} else if ( models.length > 0 ) {
			modelSelect.value = models[ 0 ].identifier;
		}
	}

	// Populate services when available
	waitForAiServices( () => {
		const services = getAvailableServices();

		serviceSelect.innerHTML = `<option value="">${ __(
			'Select Service',
			'md4ai'
		) }</option>`;

		services.forEach( ( service ) => {
			const option = document.createElement( 'option' );
			option.value = service.identifier;
			option.textContent = service.label;
			serviceSelect.appendChild( option );
		} );

		serviceSelect.disabled = false;

		// Set the currently selected service
		if (
			selectedService &&
			services.some( ( s ) => s.identifier === selectedService )
		) {
			serviceSelect.value = selectedService;
			updateModels( selectedService );
		} else if ( services.length > 0 ) {
			serviceSelect.value = services[ 0 ].identifier;
			updateModels( services[ 0 ].identifier );
		}
	} );

	serviceSelect.addEventListener( 'change', () => {
		updateModels( serviceSelect.value );
	} );
}
