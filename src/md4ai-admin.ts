
declare const aiMdData: {
	restUrl: string;
	nonce: string;
	postId: number;
	messages: {
		generating: string;
		success: string;
		error: string;
		cleared: string;
	};
};

document.addEventListener( 'DOMContentLoaded', () => {
	const generateBtn = document.getElementById(
		'md4ai-generate'
	) as HTMLButtonElement;
	let clearBtn = document.getElementById(
		'md4ai-clear'
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
    const textarea = document.getElementById(clearBtn.dataset.field) as HTMLTextAreaElement;
    if (!textarea) {
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
			updateStatus( aiMdData.messages.cleared, '#46b450' ); // Green color for success

			// Clear status after 3 seconds
			setTimeout( () => {
				updateStatus( '', '' );
			}, 3000 );
		}
	};

	/**
	 * Handles the REST API call to generate markdown from current content.
	 */
	const handleGenerate = async (): Promise< void > => {
    const textarea = document.getElementById(generateBtn.dataset.field) as HTMLTextAreaElement;
    if (!textarea) {
      return;
    }
		// Disable the button to prevent multiple submissions
		generateBtn.disabled = true;
		updateStatus( aiMdData.messages.generating, '#999' ); // Grey color for generating

		try {
			// Use the native fetch API for REST API call
			const response = await fetch(
				`${ aiMdData.restUrl }${ aiMdData.postId }`,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': aiMdData.nonce,
					},
				}
			);

			// Check if the network request was successful (status 200-299)
			if ( ! response.ok ) {
				throw new Error( `HTTP error! status: ${ response.status }` );
			}

			// Parse the JSON response
			const result = await response.json();

			if ( result.markdown ) {
				// Set the value of the textarea
				textarea.value = result.markdown;
				updateStatus( aiMdData.messages.success, '#46b450' ); // Green color for success

				// Add clear button if it doesn't exist
				if ( ! clearBtn ) {
					// Create the new button element
					const newClearBtn = document.createElement( 'button' );
					newClearBtn.type = 'button';
					newClearBtn.id = 'md4ai-clear';
					newClearBtn.className = 'button'; // WordPress button class
					newClearBtn.textContent = 'Clear Custom Markdown';
					newClearBtn.addEventListener( 'click', clearMarkdown );

					// Insert the new button after the generate button
					generateBtn.after( newClearBtn );
					clearBtn = newClearBtn; // Update the reference
				}

				// Clear status after 3 seconds
				setTimeout( () => {
					updateStatus( '', '' );
				}, 3000 );
			} else {
				// Handle missing markdown data
				updateStatus( aiMdData.messages.error, '#dc3232' ); // Red color for error
			}
		} catch ( error ) {
			// Handle network errors or JSON parsing errors
			// eslint-disable-next-line no-console
			console.error( 'REST API Error:', error );
			updateStatus( aiMdData.messages.error, '#dc3232' ); // Red color for error
		} finally {
			// Re-enable the button regardless of success or failure
			generateBtn.disabled = false;
		}
	};

	// Attach the click listener to the generate button
	generateBtn.addEventListener( 'click', handleGenerate );

	// Attach the click listener to the existing clear button if it's present
	if ( clearBtn ) {
		clearBtn.addEventListener( 'click', clearMarkdown );
	}
} );
