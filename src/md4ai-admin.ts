import { __ } from '@wordpress/i18n';
import { waitForAiServices } from './md4ai-services';

declare const aiMdData: {
	restUrl: string;
	nonce: string;
	postId: number;
	prompts: {
		'generate-markdown': string;
		'generate-llmstxt': string;
	};
};

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
	confirm: ( a: string ) => boolean;
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

function handleMd4aiButtons() {
	const generateBtn = document.querySelector(
		'.md4ai-generate'
	) as HTMLButtonElement;
	const generateAiBtn = document.querySelector(
		'.md4ai-ai-generate'
	) as HTMLButtonElement;
	let clearBtn = document.querySelector(
		'.md4ai-clear'
	) as HTMLButtonElement | null;
	const statusEl = document.getElementById( 'md4ai-status' ) as HTMLElement;

	/**
	 * Updates the status element with a message and color.
	 * @param message - The text message to display.
	 * @param color   - The CSS color for the text.
	 */
	const updateStatus = ( message: string, color: string ): void => {
		statusEl.textContent = message;
		statusEl.style.color = color;
	};

	/**
	 * Clears the custom markdown from the textarea.
	 */
	const clearMarkdown = (): void => {
		const textarea = document.getElementById(
			clearBtn.dataset.field
		) as HTMLTextAreaElement;
		if ( ! textarea ) {
			return;
		}
		// Use native confirmation dialog
		if (
			// eslint-disable-next-line no-alert
			window.confirm(
				'Are you sure you want to clear the custom markdown? Auto-generation will be used instead.'
			)
		) {
			textarea.value = '';
			updateStatus(
				__( 'Custom markdown cleared.', 'md4ai' ),
				'#46b450'
			); // Green color for success

			// Clear status after 3 seconds
			setTimeout( () => {
				updateStatus( '', '' );
			}, 3000 );
		}
	};

	function create_prompt_input() {
		const promptInput = document.createElement( 'input' );
		promptInput.type = 'text';
		promptInput.id = 'md4ai-prompt';
		promptInput.className = 'md4ai-prompt';
		promptInput.style.width = '100%';
		promptInput.placeholder = __( 'Enter your prompt', 'md4ai' );
		return promptInput;
	}

	function create_clear_button() {
		// Create the new button element
		const newClearBtn = document.createElement( 'button' );
		newClearBtn.type = 'button';
		newClearBtn.id = 'md4ai-clear';
		newClearBtn.dataset.field = generateBtn.dataset.field;
		newClearBtn.dataset.endpoint = generateBtn.dataset.endpoint;
		newClearBtn.className = 'button md4ai-clear';
		newClearBtn.textContent = __( 'Clear Custom Markdown', 'md4ai' );
		newClearBtn.addEventListener( 'click', clearMarkdown );

		// Insert the new button after the generate button
		generateBtn.after( newClearBtn );
		return newClearBtn;
	}

	function updateMarkdown( textarea: HTMLTextAreaElement, markdown: string ) {
		// Set the value of the textarea
		textarea.value = markdown;
		updateStatus(
			__( 'Markdown generated successfully!', 'md4ai' ),
			'#46b450'
		); // Green color for success

		// Add clear button if it doesn't exist
		if ( ! clearBtn ) {
			clearBtn = create_clear_button(); // Update the reference
		}

		// Clear status after 3 seconds
		setTimeout( () => {
			updateStatus( '', '' );
		}, 3000 );
	}

	/**
	 * Handles the REST API call to generate markdown from current content.
	 */
	const handleGenerate = async (): Promise< void > => {
		// Get the textarea element
		const textarea = document.getElementById(
			generateBtn.dataset.field
		) as HTMLTextAreaElement;
		if ( ! textarea ) {
			console.error(
				`Textarea ${ generateBtn.dataset.field } not found`
			);
			return;
		}

		// Get the endpoint from the dataset
		const endpoint = aiMdData.restUrl + '/' + generateBtn.dataset.endpoint;
		const url = aiMdData.postId
			? endpoint + '/' + aiMdData.postId
			: endpoint;

		// Disable the button to prevent multiple submissions
		generateBtn.disabled = true;

		// Update the status
		updateStatus( __( 'Generating…', 'md4ai' ), '#999' ); // Grey color for generating

		try {
			// Use the native fetch API for REST API call
			const response = await fetch( url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': aiMdData.nonce,
				},
			} );

			// Check if the network request was successful (status 200-299)
			if ( ! response.ok ) {
				throw new Error( `HTTP error! status: ${ response.status }` );
			}

			// Parse the JSON response
			const result = await response.json();
			if ( result.markdown ) {
				updateMarkdown( textarea, result.markdown );
			} else {
				// Handle missing markdown data
				updateStatus(
					__( 'Error generating markdown.', 'md4ai' ),
					'#dc3232'
				); // Red color for error
			}
		} catch ( error ) {
			// Handle network errors or JSON parsing errors
			// eslint-disable-next-line no-console
			console.error( 'REST API Error:', error );
			updateStatus(
				__( 'Error generating markdown.', 'md4ai' ),
				'#dc3232'
			); // Red color for error
		} finally {
			// Re-enable the button regardless of success or failure
			generateBtn.disabled = false;
		}
	};

	/**
	 * Handles the AI-enhanced markdown generation.
	 * Fetches markdown from REST API, then enhances it using AI service.
	 * @param textarea
	 * @param promptInput
	 */
	const handleAiGenerate = async (
		textarea: HTMLTextAreaElement,
		promptInput: HTMLInputElement
	): Promise< void > => {
		const { enums, helpers, store: aiStore } = window.aiServices.ai;
		const SERVICE_ARGS = {
			capabilities: [ enums.AiCapability.TEXT_GENERATION ],
		};

		if ( ! textarea ) {
			console.error(
				`Textarea ${ generateAiBtn.dataset.field } not found`
			);
			return;
		}

		// Get the endpoint from the dataset
		const endpoint =
			aiMdData.restUrl + '/' + generateAiBtn.dataset.endpoint;
		const url = aiMdData.postId
			? endpoint + '/' + aiMdData.postId
			: endpoint;

		// Disable the button to prevent multiple submissions
		generateAiBtn.disabled = true;

		// Update the status
		updateStatus( __( 'Generating with AI…', 'md4ai' ), '#999' );

		try {
			// Step 1: Fetch markdown from REST API
			updateStatus( __( 'Fetching content…', 'md4ai' ), '#999' );

			const response = await fetch( url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': aiMdData.nonce,
				},
			} );

			if ( ! response.ok ) {
				throw new Error( `HTTP error! status: ${ response.status }` );
			}

			const result = await response.json();

			if ( ! result.markdown ) {
				throw new Error( 'No markdown data received from API' );
			}

			// Step 2: Process with AI
			updateStatus( __( 'Processing with AI…', 'md4ai' ), '#999' );

			const { select } = wp.data;
			const { getAvailableService } = select( aiStore.name );

			const service = getAvailableService( SERVICE_ARGS ) as
				| false
				| {
						generateText: (
							arg: string,
							arg2: { feature: string }
						) => Promise< any >;
				  };

			if ( ! service ) {
				throw new Error( 'AI service not available' );
			}

			// Combine the prompt with the fetched markdown
			const fullPrompt = `${ promptInput.value }\n\nContent to process:\n${ result.markdown }`;

			const candidates = await service.generateText( fullPrompt, {
				feature: 'md4ai-generation',
			} );

			const aiEnhancedText = helpers.getTextFromContents(
				helpers.getCandidateContents( candidates )
			);

			// Step 3: Update the textarea with AI-enhanced content
			updateMarkdown( textarea, aiEnhancedText );
		} catch ( error ) {
			console.error( 'AI Generation Error:', error );
			updateStatus(
				__( 'Error generating AI-enhanced markdown.', 'md4ai' ),
				'#dc3232'
			);
		} finally {
			// Re-enable the button
			generateAiBtn.disabled = false;
		}
	};

	/**
	 * Initialize AI-enhanced generation with service check
	 */
	const initAiGenerate = () => {
		waitForAiServices( () => {
			// Get the textarea element
			const textarea = document.getElementById(
				generateAiBtn.dataset.field
			) as HTMLTextAreaElement;

			// append prompt input
			const promptInput = create_prompt_input();
			textarea.before( promptInput );

			// Determine which prompt to use based on the endpoint
			const prompt = aiMdData.prompts[ generateAiBtn.dataset.endpoint ];
			promptInput.value = prompt;

			// Attach the click listener once AI services are ready
			generateAiBtn.addEventListener( 'click', () =>
				handleAiGenerate( textarea, promptInput )
			);
		} );
	};

	// Attach the click listener to the generate button
	generateBtn.addEventListener( 'click', handleGenerate );

	// Initialize AI-enhanced generation
	if ( generateAiBtn ) {
		initAiGenerate();
	}

	// Attach the click listener to the existing clear button if it's present
	if ( clearBtn ) {
		clearBtn.addEventListener( 'click', clearMarkdown );
	}
}

document.addEventListener( 'DOMContentLoaded', handleMd4aiButtons );
