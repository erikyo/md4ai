import { __ } from '@wordpress/i18n';
import { generateAiText, waitForAiServices } from './md4ai-services';
import './style.scss';
import {Md4aiData} from "./types";

// Declare global variables
declare const md4aiData: Md4aiData;

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

/**
 * Main handler for MD4AI button logic and UI interactions.
 */
export function handleMd4aiButtons() {
	// --- DOM Elements ---
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

	// --- Constants ---
	const COLORS = {
		SUCCESS: '#00d084',
		ERROR: '#cf2e2e',
		LOADING: '#999',
	};

	/**
	 * Updates the status element with a message and color.
	 * @param {string} message - The text message to display.
	 * @param {string} color   - The CSS color for the text.
	 */
	const updateStatus = ( message: string, color: string ): void => {
		if ( ! statusEl ) {
			return;
		}
		statusEl.textContent = message;
		statusEl.style.color = color;
	};

	/**
	 * Dispatches an input event to ensure listeners (like the previewer) detect the change.
	 * @param {HTMLTextAreaElement} textarea - The element to trigger the event on.
	 */
	const triggerChangeEvent = ( textarea: HTMLTextAreaElement ): void => {
		textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	};

	/**
	 * Clears the custom markdown from the textarea.
	 */
	const clearMarkdown = (): void => {
		const fieldId = clearBtn?.dataset.field;
		const textarea = document.getElementById(
			fieldId || ''
		) as HTMLTextAreaElement;

		if ( ! textarea ) {
			return;
		}

		// Use native confirmation dialog
		// eslint-disable-next-line no-alert
		if (
			window.confirm(
				__(
					'Are you sure you want to clear the custom markdown? Auto-generation will be used instead.',
					'md4ai'
				)
			)
		) {
			textarea.value = '';

			// Trigger update for previewer
			triggerChangeEvent( textarea );

			updateStatus(
				__( 'Custom markdown cleared.', 'md4ai' ),
				COLORS.SUCCESS
			);

			// Clear status after 3 seconds
			setTimeout( () => {
				updateStatus( '', '' );
			}, 3000 );
		}
	};

	/**
	 * Creates a prompt text area element for AI input.
	 * @return {HTMLTextAreaElement} The created text area element.
	 */
	function create_prompt_text_area(): HTMLTextAreaElement {
		const promptInput = document.createElement( 'textarea' );
		promptInput.id = 'md4ai-prompt';
		promptInput.className = 'md4ai-prompt';
		promptInput.style.width = '100%';
		promptInput.placeholder = __( 'Enter your prompt', 'md4ai' );
		return promptInput;
	}

	/**
	 * Creates a clear button element if it doesn't exist.
	 * @return {HTMLButtonElement} The created button element.
	 */
	function create_clear_button(): HTMLButtonElement {
		const newClearBtn = document.createElement( 'button' );
		newClearBtn.type = 'button';
		newClearBtn.id = 'md4ai-clear';
		// Inherit data attributes from main button
		newClearBtn.dataset.field = generateBtn.dataset.field;
		newClearBtn.dataset.endpoint = generateBtn.dataset.endpoint;
		newClearBtn.className = 'button md4ai-clear';
		newClearBtn.textContent = __( 'Clear Custom Markdown', 'md4ai' );
		newClearBtn.addEventListener( 'click', clearMarkdown );

		// Insert the new button after the generate button
		generateBtn.after( newClearBtn );
		return newClearBtn;
	}

	/**
	 * Updates the markdown in the textarea and handles UI feedback.
	 * @param {HTMLTextAreaElement} textarea - The textarea element to update.
	 * @param {string}              markdown - The markdown content to set.
	 */
	function updateMarkdown(
		textarea: HTMLTextAreaElement,
		markdown: string
	): void {
		// Set the value of the textarea
		textarea.value = markdown;

		// Vital: Notify other scripts (previewer) that the value changed programmatically
		triggerChangeEvent( textarea );

		updateStatus(
			__( 'Markdown generated successfully!', 'md4ai' ),
			COLORS.SUCCESS
		);

		// Add clear button if it doesn't exist
		if ( ! clearBtn ) {
			clearBtn = create_clear_button();
		}

		// Clear status after 3 seconds
		setTimeout( () => {
			updateStatus( '', '' );
		}, 3000 );
	}

	/**
	 * Handles the REST API call to generate markdown from current content (Standard Generation).
	 */
	const handleGenerate = async (): Promise< void > => {
		const fieldId = generateBtn.dataset.field;
		const textarea = document.getElementById(
			fieldId || ''
		) as HTMLTextAreaElement;

		if ( ! textarea ) {
			console.error( `Textarea ${ fieldId } not found` );
			return;
		}

		// Construct URL
		const endpoint = `${ md4aiData.restUrl }/${ generateBtn.dataset.endpoint }`;
		const url = md4aiData.postId
			? `${ endpoint }/${ md4aiData.postId }`
			: endpoint;

		// Disable UI
		generateBtn.disabled = true;
		updateStatus( __( 'Generating…', 'md4ai' ), COLORS.LOADING );

		try {
			const response = await fetch( url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': md4aiData.nonce,
				},
			} );

			if ( ! response.ok ) {
				throw new Error( `HTTP error! status: ${ response.status }` );
			}

			const result = await response.json();

			if ( result.markdown ) {
				updateMarkdown( textarea, result.markdown );
			} else {
				updateStatus(
					__( 'Error generating markdown.', 'md4ai' ),
					COLORS.ERROR
				);
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'REST API Error:', error );
			updateStatus(
				__( 'Error generating markdown.', 'md4ai' ),
				COLORS.ERROR
			);
		} finally {
			generateBtn.disabled = false;
		}
	};

	/**
	 * Handles the AI-enhanced markdown generation.
	 * Flow: Fetch existing content -> Process via AI Service -> Update UI.
	 * @param {HTMLTextAreaElement} textarea    - The target field.
	 * @param {HTMLTextAreaElement} promptInput - The user prompt input.
	 */
	const handleAiGenerate = async (
		textarea: HTMLTextAreaElement,
		promptInput: HTMLTextAreaElement
	): Promise< void > => {
		if ( ! textarea ) {
			console.error(
				`Textarea ${ generateAiBtn.dataset.field } not found`
			);
			return;
		}

		// Construct URL
		const endpoint = `${ md4aiData.restUrl }/${ generateAiBtn.dataset.endpoint }`;
		const url = md4aiData.postId
			? `${ endpoint }/${ md4aiData.postId }`
			: endpoint;

		// Disable UI
		generateAiBtn.disabled = true;
		updateStatus( __( 'Generating with AI…', 'md4ai' ), COLORS.LOADING );

		try {
			// Step 1: Fetch markdown from REST API
			updateStatus( __( 'Fetching content…', 'md4ai' ), COLORS.LOADING );

			const response = await fetch( url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': md4aiData.nonce,
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
			updateStatus(
				__( 'Processing with AI…', 'md4ai' ),
				COLORS.LOADING
			);

			// Combine the prompt with the fetched markdown
			const fullPrompt = `${ promptInput.value }\n\nContent to process:\n${ result.markdown }`;

			const generated = await generateAiText( fullPrompt );

			// Step 3: Update the textarea with AI-enhanced content
			updateMarkdown( textarea, generated );
		} catch ( error ) {
			console.error( 'AI Generation Error:', error );
			updateStatus(
				__( 'Error generating AI-enhanced markdown.', 'md4ai' ),
				COLORS.ERROR
			);
		} finally {
			generateAiBtn.disabled = false;
		}
	};

	/**
	 * Initialize AI-enhanced generation with service check.
	 * Waits for WP AI services to be available before attaching listeners.
	 */
	const initAiGenerate = (): void => {
		waitForAiServices( () => {
			const fieldId = generateAiBtn.dataset.field;
			const textarea = document.getElementById(
				fieldId || ''
			) as HTMLTextAreaElement;

			if ( ! textarea ) {
				return;
			}

			// Append prompt input
			const promptInput = create_prompt_text_area();
			textarea.after( promptInput );

			// Determine which prompt to use based on the endpoint
			const endpointKey = generateAiBtn.dataset.endpoint;
			const prompt =
				endpointKey && md4aiData.prompts[ endpointKey ]
					? md4aiData.prompts[ endpointKey ]
					: '';
			promptInput.value = prompt;

			// Attach the click listener once AI services are ready
			generateAiBtn.addEventListener( 'click', () =>
				handleAiGenerate( textarea, promptInput )
			);
		} );
	};

	// --- Initialization ---

	// Attach the click listener to the generate button
	if ( generateBtn ) {
		generateBtn.addEventListener( 'click', handleGenerate );
	}

	// Initialize AI-enhanced generation
	if ( generateAiBtn ) {
		initAiGenerate();
	}

	// Attach the click listener to the existing clear button if it's present
	if ( clearBtn ) {
		clearBtn.addEventListener( 'click', clearMarkdown );
	}
}
