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
			helpers: any;
			store: any;
		};
	};
};


/**
 * Wait for AI services to be available and run the AI logic.
 * @param fn
 */
function waitForAiServices( fn: () => void ) {
  const { enums, store: aiStore } = window.aiServices.ai;
  const SERVICE_ARGS = { capabilities: [ enums.AiCapability.TEXT_GENERATION ] };

	const { select, subscribe } = wp.data;
	function checkAndRun() {
		try {
			const { hasAvailableServices } = select( aiStore.name );

			if ( hasAvailableServices( SERVICE_ARGS ) ) {
        console.log('AI services are available');
				/** ready */
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
			alert( error );
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
				alert( error );
			}
		}
	} );
}

export { waitForAiServices };
