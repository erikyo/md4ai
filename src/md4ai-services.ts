import {Window, WP} from "./types";

declare const wp: WP;

declare const window: Window;

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
 * Generates AI text using the available service.
 *
 * @param fullPrompt The full prompt to generate text from
 * @returns The generated text
 * @throws Error if AI service is not available
 */
async function generateAiText( fullPrompt: string ) {
	const { enums, helpers, store: aiStore } = window.aiServices.ai;
	const SERVICE_ARGS = {
		capabilities: [ enums.AiCapability.TEXT_GENERATION ],
	};

	const { select } = wp.data;
	const { getAvailableService } = select( aiStore.name );

	const service = getAvailableService( SERVICE_ARGS );

	if ( ! service ) {
		throw new Error( 'AI service not available' );
	}

	const candidates = await service.generateText( fullPrompt, {
		feature: 'md4ai-generation',
	} );

	let generated = helpers.getTextFromContents(
		helpers.getCandidateContents( candidates )
	);

	// Sometimes we can find the whole response wrapped with ```text or ```markdown from the beginning. in this case we should remove it
	if ( generated.startsWith( '```' ) ) {
		generated = generated.replace( /^```text|```markdown/g, '' );

		// then remove the last ```
		generated = generated.replace( /```$/g, '' );
	}

	return generated;
}

export { waitForAiServices, generateAiText };
