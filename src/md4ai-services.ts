import { Window, WP } from './types';

declare const wp: WP;

declare const window: Window;

/**
 * Wait for AI services to be available and run the AI logic.
 *
 * @param fn The function to run when AI services are available
 * @throws Error if AI services are not available
 */
export type AiServiceOption = {
	identifier: string;
	label: string;
};

export type AiModelOption = {
	identifier: string;
	label: string;
};

/**
 * Wait for AI services to be available and run the AI logic.
 *
 * @param fn The function to run when AI services are available
 * @throws Error if AI services are not available
 */
function waitForAiServices( fn: () => void ) {
	// Check if aiServices is available, if not, return
	if ( ! window.aiServices ) {
		return;
	}

	// Get aiServices
	const { enums, store: aiStore } = window.aiServices.ai;
	const SERVICE_ARGS = {
		capabilities: [ enums.AiCapability.TEXT_GENERATION ],
	};

	const { select, subscribe } = wp.data;

	function checkAndRun() {
		try {
			const { hasAvailableServices } = select( aiStore.name );
			if ( hasAvailableServices( SERVICE_ARGS ) ) {
				return true;
			}
		} catch ( error ) {
			throw error;
		}
		return false;
	}

	// Try immediately first
	if ( checkAndRun() ) {
		try {
			fn();
		} catch ( error ) {
			throw error;
		}
	}

	// If not available, subscribe to changes
	const unsubscribe = subscribe( () => {
		if ( checkAndRun() ) {
			unsubscribe();
			try {
				fn();
			} catch ( error ) {
				throw error;
			}
		}
	} );
}

/**
 * Get available AI services that support text generation.
 *
 * @return List of available services
 */
function getAvailableServices(): AiServiceOption[] {
	if ( ! window.aiServices ) {
		return [];
	}

	const { enums, store: aiStore } = window.aiServices.ai;
	const { select } = wp.data;
	const { getServices } = select( aiStore.name );

	const services = getServices();
	if ( ! services ) {
		return [];
	}

	return Object.values( services )
		.filter( ( service: any ) => {
			if ( ! service.is_available ) {
				return false;
			}
			return ( service.metadata?.capabilities || [] ).includes(
				enums.AiCapability.TEXT_GENERATION
			);
		} )
		.map( ( { slug, metadata }: any ) => ( {
			identifier: slug,
			label: metadata?.name || slug,
		} ) );
}

/**
 * Get available models for a specific service that support text generation.
 *
 * @param serviceIdentifier The service identifier
 * @return List of available models
 */
function getAvailableModels( serviceIdentifier: string ): AiModelOption[] {
	if ( ! window.aiServices || ! serviceIdentifier ) {
		return [];
	}

	const { enums, store: aiStore } = window.aiServices.ai;
	const { select } = wp.data;
	const { getServices } = select( aiStore.name );

	const services = getServices();
	if ( ! services || ! services[ serviceIdentifier ] ) {
		return [];
	}

	const service = services[ serviceIdentifier ];
	return Object.values( service.available_models )
		.filter( ( modelMetadata: any ) => {
			return modelMetadata.capabilities.includes(
				enums.AiCapability.TEXT_GENERATION
			);
		} )
		.map( ( modelMetadata: any ) => ( {
			identifier: modelMetadata.slug,
			label: modelMetadata.name,
		} ) );
}

/**
 * Generates AI text using the available service.
 *
 * @param fullPrompt        The full prompt to generate text from
 * @param serviceIdentifier Optional service identifier
 * @param modelIdentifier   Optional model identifier
 * @return The generated text
 * @throws Error if AI service is not available
 */
async function generateAiText(
	fullPrompt: string,
	serviceIdentifier?: string,
	modelIdentifier?: string
) {
	const { enums, helpers, store: aiStore } = window.aiServices.ai;
	const SERVICE_ARGS = {
		capabilities: [ enums.AiCapability.TEXT_GENERATION ],
	};

	const { select } = wp.data;
	const { getAvailableService } = select( aiStore.name );

	let service;

	if ( serviceIdentifier ) {
		service = getAvailableService( serviceIdentifier as any );
	} else {
		service = getAvailableService( SERVICE_ARGS );
	}

	if ( ! service ) {
		throw new Error( 'AI service not available' );
	}

	const options: any = {
		feature: 'md4ai-generation',
	};

	if ( modelIdentifier ) {
		options.model = modelIdentifier;
	}

	const candidates = await service.generateText( fullPrompt, options );

	let generated = helpers.getTextFromContents(
		helpers.getCandidateContents( candidates )
	);

	// Sometimes we can find the whole response wrapped with ```text or ```markdown from the beginning. in this case we should remove it
	if ( generated.startsWith( '```' ) ) {
		generated = generated.replace( /^```(?:text|markdown)?\s*/, '' );

		// then remove the last ```
		generated = generated.replace( /\s*```$/, '' );
	}

	return generated;
}

export {
	waitForAiServices,
	generateAiText,
	getAvailableServices,
	getAvailableModels,
};
