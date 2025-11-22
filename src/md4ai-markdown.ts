import { marked } from 'marked';
import DOMPurify from 'dompurify';

/**
 * Initializes the Markdown preview functionality.
 * Listens for changes in the target textarea and updates the preview pane.
 */
export function md4ai_markdown(): void {
	// Select required elements
	const md4ai_generate_button = document.querySelector(
		'.md4ai-generate'
	) as HTMLButtonElement | null;

	if ( ! md4ai_generate_button ) {
		return;
	}

	const md4ai_field_id = md4ai_generate_button.dataset.field;
	const md4ai_textfield = document.querySelector(
		`#${ md4ai_field_id }`
	) as HTMLTextAreaElement;

	if ( md4ai_textfield ) {
		const preview_content = document.querySelector(
			'#md4ai-preview-content'
		) as HTMLDivElement;

		/**
		 * Parses markdown and updates the preview HTML.
		 * @param {string} newMarkdown - The raw markdown text.
		 */
		const updateContent = ( newMarkdown: string ): void => {
			if ( ! preview_content ) {
				return;
			}

			// Parse markdown to HTML
			const html = marked.parse( newMarkdown );
			// Sanitize HTML to prevent XSS
			const sanitizedHtml = DOMPurify.sanitize( html as string );

			preview_content.innerHTML = sanitizedHtml;
		};

		// Initial render on load
		updateContent( md4ai_textfield.value );

		// Debounce timer reference
		let debounceTimer: number | undefined;

		/**
		 * Event Listener for input changes.
		 * Uses 'input' instead of 'change' to capture real-time typing.
		 * Also captures programmatic events dispatched via .dispatchEvent(new Event('input'))
		 */
		md4ai_textfield.addEventListener( 'input', () => {
			// Clear existing timer
			if ( debounceTimer ) {
				window.clearTimeout( debounceTimer );
			}

			// Set new debounce timer (300ms)
			debounceTimer = window.setTimeout( () => {
				updateContent( md4ai_textfield.value );
			}, 300 );
		} );
	}
}
