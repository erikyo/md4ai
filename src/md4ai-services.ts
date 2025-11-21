declare const wp: {
	data: {
		select: ( arg: string ) => {
			isServiceAvailable: ( a: string ) => boolean;
			hasAvailableServices: ( a?: { capabilities: string[] } ) => boolean;
			getAvailableService: ( a?: { capabilities: string[] } ) => boolean;
		};
		subscribe: ( callback: () => void, storeName?: string ) => () => void;
	};
};

declare const window: {
	addEventListener: ( a: string, b: () => void ) => void;
	aiServices: {
		ai: {
			enums: {
				AiCapability: {
					MULTIMODAL_INPUT: string;
					TEXT_GENERATION: string;
				};
			};
			helpers: {
				getTextFromContents: ( arg: string ) => string;
				getCandidateContents: ( arg: string ) => string;
			};
			store: {
				name: string;
			};
		};
	};
};

/**
 * Wait for AI services to be available and run the AI logic.
 * @param fn
 */
function waitForAiServices( fn: () => void ) {
  // Check if aiServices is available, if not, return
  if (!window.aiServices) return;

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
			console.error( error );
			return false;
		}
		return false;
	}

	// Try immediately first
	if ( checkAndRun() ) {
		try {
			fn();
		} catch ( error ) {
			console.error( error );
		}
		return;
	}

	// If not available, subscribe to changes
	const unsubscribe = subscribe( () => {
		if ( checkAndRun() ) {
			unsubscribe();
			try {
				fn();
			} catch ( error ) {
				console.error( error );
			}
		}
	} );
}

export { waitForAiServices };
