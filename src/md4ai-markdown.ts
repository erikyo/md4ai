import { marked } from 'marked';
import DOMPurify from 'dompurify';

/**
 * Initializes the Markdown preview functionality.
 * Listens for changes in the target textarea and updates the preview pane.
 */
export function md4aiMarkdown(): void {
	// Select required elements
	const md4aiGenerateButton = document.querySelector(
		'.md4ai-generate'
	) as HTMLButtonElement | null;

	if ( ! md4aiGenerateButton ) {
		return;
	}

	const md4aiFieldId = md4aiGenerateButton.dataset.field;
	const md4aiTextfield = document.querySelector(
		`#${ md4aiFieldId }`
	) as HTMLTextAreaElement;

	if ( md4aiTextfield ) {
		const previewContent = document.querySelector(
			'#md4ai-preview-content'
		) as HTMLDivElement;

		/**
		 * Parses markdown and updates the preview HTML.
		 * @param {string} newMarkdown - The raw markdown text.
		 */
		const updateContent = ( newMarkdown: string ): void => {
			if ( ! previewContent ) {
				return;
			}

			// Parse markdown to HTML
			const html = marked.parse( newMarkdown );
			// Sanitize HTML to prevent XSS
			const sanitizedHtml = DOMPurify.sanitize( html as string );

			previewContent.innerHTML = sanitizedHtml;
		};

		// Initial render on load
		updateContent( md4aiTextfield.value );

		// Debounce timer reference
		let debounceTimer: number | undefined;

		/**
		 * Event Listener for input changes.
		 * Uses 'input' instead of 'change' to capture real-time typing.
		 * Also captures programmatic events dispatched via .dispatchEvent(new Event('input'))
		 */
		md4aiTextfield.addEventListener( 'input', () => {
			// Clear existing timer
			if ( debounceTimer ) {
				window.clearTimeout( debounceTimer );
			}

			// Set new debounce timer (300ms)
			debounceTimer = window.setTimeout( () => {
				updateContent( md4aiTextfield.value );
			}, 300 );
		} );
	}
}
