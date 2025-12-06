// Declare global variables
import { generateAiText, waitForAiServices } from './md4ai-services';
import { GeoInsightsResult, Md4aiData } from './types';
import { __ } from '@wordpress/i18n';

declare const md4aiData: Md4aiData;

/**
 * Updates the gauge chart with the given percentage.
 * @param selector   The chart selector
 * @param percentage The percentage to display
 */
function updateGaugeChart( selector: string, percentage: number ) {
	const chart = document.querySelector( selector );
	if ( ! chart ) {
		return;
	}

	const circle = chart.querySelector( '.circle' ) as SVGCircleElement;
	const text = chart.querySelector( '.percentage' ) as SVGTextElement;

	if ( circle && text ) {
		// Calculate the circumference and dasharray
		const radius = 15.9155;
		const circumference = 2 * Math.PI * radius;
		const dashArray = ( percentage / 100 ) * circumference;

		circle.setAttribute(
			'stroke-dasharray',
			`${ dashArray } ${ circumference }`
		);
		text.textContent = percentage.toString();
	}

	// Update color class based on score
	updateGaugeColor( chart as SVGElement, percentage );
}

/**
 * Updates the gauge color based on score
 * @param chart
 * @param score
 */
function updateGaugeColor( chart: SVGElement, score: number ) {
	chart.classList.remove( 'green', 'orange', 'red', 'blue', 'purple' );
	chart.classList.add( getScoreClass( score ) );
}

/**
 * Returns the CSS class for the given score.
 * @param score The score to get the class for
 * @return The CSS class
 */
function getScoreClass( score: number ): string {
	if ( score >= 90 ) {
		return 'green';
	}
	if ( score >= 50 ) {
		return 'orange';
	}
	return 'red';
}

/**
 * Returns the color for the given score.
 * @param score The score to get the color for
 * @return The color for the score
 */
function getScoreColor( score: number ): string {
	if ( score >= 90 ) {
		return 'green';
	}
	if ( score >= 50 ) {
		return 'orange';
	}
	return 'red';
}

/**
 * Creates a suggestion box for the given corrections.
 * @param corrections The corrections to display
 * @return The suggestion box HTML
 */
function createSuggestionBox(
	corrections: GeoInsightsResult[ 'corrections' ]
): string {
	if ( ! corrections || corrections.length === 0 ) {
		return `
      <div class="geo-suggestion-box geo-success">
        <div class="suggestion-header">
          <span class="suggestion-icon">✓</span>
          <h3>${ __( 'All Checks Passed', 'md4ai' ) }</h3>
        </div>
        <p>${ __(
			"Your website's identity is properly configured for AI systems.",
			'md4ai'
		) }</p>
      </div>
    `;
	}

	const suggestionItems = corrections
		.map(
			( correction ) => `
    <div class="suggestion-item">
      <div class="suggestion-title">
        <span class="suggestion-bullet">▸</span>
        <strong>${ correction.field }</strong>
      </div>
      <div class="suggestion-details">
        <div class="suggestion-row">
          <span class="label">AI Detected:</span>
          <span class="value ai-value">${ correction.ai_value.substring(
				0,
				100
			) }${ correction.ai_value.length > 100 ? '...' : '' }</span>
        </div>
        <div class="suggestion-row">
          <span class="label">${ __( 'Expected:', 'md4ai' ) }</span>
          <span class="value expected-value">${ correction.real_value }</span>
        </div>
        <div class="suggestion-tip">
          <span class="tip-icon">💡</span>
          ${ correction.tip }
        </div>
      </div>
    </div>
  `
		)
		.join( '' );

	return `
    <div class="geo-suggestion-box geo-warning">
      <div class="suggestion-header">
        <span class="suggestion-icon">⚠</span>
        <h3>${ __( 'Opportunities for Improvement', 'md4ai' ) }</h3>
      </div>
      <p class="suggestion-intro">${ __(
			"AI systems detected the following discrepancies. Fixing these will improve your site's visibility:",
			'md4ai'
		) }</p>
      <div class="suggestion-list">
        ${ suggestionItems }
      </div>
    </div>
  `;
}

/**
 * Displays the results in the results div.
 * @param data The results data
 */
function displayResults( data: GeoInsightsResult ) {
	const resultsDiv = document.getElementById( 'geo-results' );
	const vitalsContainer = document.getElementById( 'geo-vitals-container' );
	const opportunitiesContainer = document.getElementById(
		'geo-opportunities-container'
	);
	const reportContainer = document.getElementById( 'geo-report-container' );

	if ( ! resultsDiv ) {
		return;
	}

	// Calculate scores
	const authScore = data.raw_ai_data.score_auth * 10;
	const relevanceScore = data.raw_ai_data.score_relevance * 10;
	const dataScore = data.raw_ai_data.score_data * 10;
	const crawlerScore = data.raw_ai_data.score_crawler * 10;

	// Update gauge charts using data-metric attribute
	const gaugeCards = resultsDiv.querySelectorAll( '.geo-gauge-card' );
	gaugeCards.forEach( ( card ) => {
		const metric = card.getAttribute( 'data-metric' );
		const chart = card.querySelector( '.circular-chart' ) as SVGElement;
		let score = 0;

		switch ( metric ) {
			case 'authority':
				score = authScore;
				break;
			case 'relevance':
				score = relevanceScore;
				break;
			case 'knowledge':
				score = dataScore;
				break;
			case 'crawler':
				score = crawlerScore;
				break;
		}

		if ( chart ) {
			const circle = chart.querySelector( '.circle' ) as SVGCircleElement;
			const text = chart.querySelector( '.percentage' ) as SVGTextElement;

			if ( circle && text ) {
				circle.setAttribute( 'stroke-dasharray', `${ score }, 100` );
				text.textContent = Math.round( score ).toString();
			}

			// Update color class based on score
			chart.classList.remove(
				'green',
				'orange',
				'red',
				'blue',
				'purple'
			);
			chart.classList.add( getScoreClass( score ) );
		}
	} );

	// Create Core Web Vitals-style metrics section
	if ( vitalsContainer ) {
		const vitalsHTML = `
      <div class="geo-vitals-section">
        <h2 class="geo-section-title">
          <span class="dashicons dashicons-chart-bar"></span>
          ${ __( 'Performance Metrics', 'md4ai' ) }
        </h2>
        <div class="geo-vitals-grid">
          <div class="geo-vital-card">
            <div class="geo-vital-indicator ${ getScoreClass(
				data.scores.identity_match
			) }">
              ${ data.scores.identity_match }%
            </div>
            <div class="geo-vital-content">
              <div class="geo-vital-label">${ __(
					'Identity Match',
					'md4ai'
				) }</div>
              <div class="geo-vital-bar">
                <div class="geo-vital-bar-fill ${ getScoreClass(
					data.scores.identity_match
				) }" style="width: ${ data.scores.identity_match }%"></div>
              </div>
            </div>
          </div>
          <div class="geo-vital-card">
            <div class="geo-vital-indicator ${ getScoreClass(
				data.scores.tech_match
			) }">
              ${ data.scores.tech_match }%
            </div>
            <div class="geo-vital-content">
              <div class="geo-vital-label">${ __(
					'Technical Match',
					'md4ai'
				) }</div>
              <div class="geo-vital-bar">
                <div class="geo-vital-bar-fill ${ getScoreClass(
					data.scores.tech_match
				) }" style="width: ${ data.scores.tech_match }%"></div>
              </div>
            </div>
          </div>
          <div class="geo-vital-card">
            <div class="geo-vital-indicator ${ getScoreClass(
				data.scores.ai_perception
			) }">
              ${ data.scores.ai_perception }%
            </div>
            <div class="geo-vital-content">
              <div class="geo-vital-label">${ __(
					'AI Perception',
					'md4ai'
				) }</div>
              <div class="geo-vital-bar">
                <div class="geo-vital-bar-fill ${ getScoreClass(
					data.scores.ai_perception
				) }" style="width: ${ data.scores.ai_perception }%"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- New GEO Metrics Row -->
        <h3 class="geo-subsection-title" style="margin-top: 20px; margin-bottom: 10px; font-size: 14px; color: #666;">${ __(
			'Detailed GEO Factors',
			'md4ai'
		) }</h3>
        <div class="geo-vitals-grid">
           <div class="geo-vital-card">
            <div class="geo-vital-indicator ${ getScoreClass(
				data.scores.geo_structure
			) }">
              ${ data.scores.geo_structure }
            </div>
            <div class="geo-vital-content">
              <div class="geo-vital-label">${ __(
					'Content Structure',
					'md4ai'
				) }</div>
              <div class="geo-vital-bar">
                <div class="geo-vital-bar-fill ${ getScoreClass(
					data.scores.geo_structure
				) }" style="width: ${ data.scores.geo_structure }%"></div>
              </div>
            </div>
          </div>
           <div class="geo-vital-card">
            <div class="geo-vital-indicator ${ getScoreClass(
				data.scores.geo_multimedia
			) }">
              ${ data.scores.geo_multimedia }
            </div>
            <div class="geo-vital-content">
              <div class="geo-vital-label">${ __(
					'Multimedia Usage',
					'md4ai'
				) }</div>
              <div class="geo-vital-bar">
                <div class="geo-vital-bar-fill ${ getScoreClass(
					data.scores.geo_multimedia
				) }" style="width: ${ data.scores.geo_multimedia }%"></div>
              </div>
            </div>
          </div>
           <div class="geo-vital-card">
            <div class="geo-vital-indicator ${ getScoreClass(
				data.scores.geo_tech
			) }">
              ${ data.scores.geo_tech }
            </div>
            <div class="geo-vital-content">
              <div class="geo-vital-label">${ __(
					'Tech SEO Perception',
					'md4ai'
				) }</div>
              <div class="geo-vital-bar">
                <div class="geo-vital-bar-fill ${ getScoreClass(
					data.scores.geo_tech
				) }" style="width: ${ data.scores.geo_tech }%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
		vitalsContainer.innerHTML = vitalsHTML;
	}

	// Create suggestions box (opportunities section)
	if ( opportunitiesContainer ) {
		const suggestionsHTML = createSuggestionBox( data.corrections );
		opportunitiesContainer.innerHTML = suggestionsHTML;
	}

	// Create detailed report
	if ( reportContainer ) {
		const reportHTML = `
      <div class="geo-detailed-report">
        <h2 class="geo-section-title">
          <span class="dashicons dashicons-info-outline"></span>
          ${ __( 'AI Knowledge Analysis', 'md4ai' ) }
        </h2>
        <div class="report-grid">
          <div class="report-item">
            <span class="report-label">${ __(
				'Website Name:',
				'md4ai'
			) }</span>
            <span class="report-value">${
				data.ground_truth.website_name
			}</span>
          </div>
          <div class="report-item">
            <span class="report-label">${ __( 'Author:', 'md4ai' ) }</span>
            <span class="report-value">${ data.ground_truth.author_name }</span>
          </div>
          <div class="report-item">
            <span class="report-label">${ __( 'Topics:', 'md4ai' ) }</span>
            <span class="report-value">${ data.ground_truth.topics.join(
				', '
			) }</span>
          </div>
          <div class="report-item">
            <span class="report-label">${ __( 'E-Commerce:', 'md4ai' ) }</span>
            <span class="report-value">${
				data.ground_truth.is_ecommerce
			}</span>
          </div>
        </div>
      </div>
    `;
		reportContainer.innerHTML = reportHTML;
	}

	resultsDiv.style.display = 'block';
}

/**
 * Builds the prompt template for the insights.
 * @return The prompt template
 */
function buildPromptTemplate() {
	let dynamicSection = '';
	const isWooActive = md4aiData.woo_active;

	if ( isWooActive ) {
		dynamicSection = `4. E-COMMERCE DETECTION
Is an E-commerce site: [Yes / No]
Reasoning for Detection: [Complete]
6. PRODUCT CATALOG
Main Product Categories: [List 3-5]
Specific Products Known: [List 3 SKUs]
Estimated Best Sellers: [Complete]`;
	}

	const promptTemplate = `Act as a Senior AI Search Engineer and SEO Specialist. Your task is to analyze your internal knowledge base regarding the following domain: ${ md4aiData.blogUrl }
You must output your analysis strictly following the schema below. Do not write conversational text. Use "Unknown" or "N/A" if needed.
--- BEGIN ANALYSIS REPORT ---
1. GENERAL OVERVIEW
Website Name: [Complete]
Author Name: [Complete]
Primary Subject Matter: [Max 10 words]
Target Audience: [Ideal customer]
Perceived Authority Level: [Low / Medium / High / Leader]
Relevance Context: [Why is this relevant?]
2. CONTENT & TOPICS
Core Topic 1: [Complete]
Core Topic 2: [Complete]
Core Topic 3: [Complete]
Notable Content: [Specific page]
3. KNOWLEDGE & ENTITIES
Main Entity Type: [Organization / Person / LocalBusiness / Brand]
Knowledge Graph Presence: [Likely / Unlikely]
Key Entities Identified: [List 3 main entities mentioned]
Topic Cluster Consistency: [High / Medium / Low]
${ dynamicSection }
FINAL EVALUATION OF THE DOMAIN
Authoritative Content: [0 to 10 where 10 is authoritative]
Contextual Relevance: [0 to 10 where 10 is very relevant]
Amount of data available: [0 to 10 where 10 is a lot]
The website is intelligible to crawlers: [0 to 10 where 10 is very intelligible]

--- END ANALYSIS REPORT ---`;

	return promptTemplate;
}

/**
 * Initializes the geo insights.
 */
export function initGeoInsights() {
	const btnStart = document.getElementById(
		'btn-start-analysis'
	) as HTMLButtonElement;
	const loadingDiv = document.getElementById( 'geo-loading' );
	const resultsDiv = document.getElementById( 'geo-results' );
	const urlInput = document.getElementById(
		'geo-url-input'
	) as HTMLInputElement;
	const wrapper = document.querySelector(
		'.geo-insights-wrapper'
	) as HTMLElement;
	const siteDomain = wrapper?.dataset.siteDomain || '';

	/**
	 * Validates that the URL is within the allowed domain
	 * @param url
	 */
	function validateUrl( url: string ): boolean {
		try {
			const parsedUrl = new URL( url );
			return (
				parsedUrl.hostname === siteDomain ||
				parsedUrl.hostname.endsWith( '.' + siteDomain )
			);
		} catch {
			return false;
		}
	}

	/**
	 * Show error message for invalid URL
	 */
	function showUrlError() {
		if ( urlInput ) {
			urlInput.style.borderColor = '#ff4e42';
			urlInput.style.boxShadow = '0 0 0 3px rgba(255, 78, 66, 0.15)';

			// Remove error styling after 3 seconds
			setTimeout( () => {
				urlInput.style.borderColor = '';
				urlInput.style.boxShadow = '';
			}, 3000 );
		}
	}

	/**
	 * Show error message when AI Services is not available
	 */
	function showErrorAiServices() {
		console.error(
			'Error: Please install Ai services in order to enable GEO insights'
		);
		if ( btnStart ) {
			btnStart.innerHTML = `<span class="dashicons dashicons-warning"></span> ${ __(
				'AI Services Required',
				'md4ai'
			) }`;
			btnStart.classList.add( 'disabled' );
			btnStart.disabled = true;
		}
		if ( resultsDiv ) {
			resultsDiv.innerHTML = `
        <div class="geo-error-message">
          <span class="dashicons dashicons-plugins-checked"></span>
          <div>
            <h3>${ __( 'AI Services Plugin Required', 'md4ai' ) }</h3>
            <p>${ __(
				'Install the AI Services plugin to analyze your website with AI.',
				'md4ai'
			) }</p>
            <a href="${
				md4aiData.blogUrl
			}/wp-admin/plugin-install.php?s=AI%2520Services&tab=search&type=term" target="_blank" class="geo-analyze-button">
              <span class="dashicons dashicons-download"></span>
              ${ __( 'Install Plugin', 'md4ai' ) }
            </a>
          </div>
        </div>
      `;
			resultsDiv.style.display = 'block';
		}
		if ( loadingDiv ) {
			loadingDiv.style.display = 'none';
		}
	}

	/**
	 * Updates the loading step text
	 * @param step
	 */
	function updateLoadingStep( step: string ) {
		const stepEl = document.getElementById( 'geo-loading-step' );
		if ( stepEl ) {
			stepEl.textContent = step;
		}
	}

	/**
	 * Initializes Geo Insights analysis.
	 * @param {string} promptTemplate - The AI prompt to generate text.
	 * @return {Promise<void>} - A promise that resolves when the analysis is complete.
	 */
	async function GeoInsightsInit( promptTemplate: string ): Promise< void > {
		updateLoadingStep( __( 'Generating AI analysis…', 'md4ai' ) );

		const generated = await generateAiText( promptTemplate );
		console.log( generated );

		updateLoadingStep( __( 'Processing results…', 'md4ai' ) );

		const response = await fetch( md4aiData.restUrl + '/geo-insights', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': md4aiData.nonce,
			},
			body: JSON.stringify( {
				content: generated,
			} ),
		} );

		const data = await response.json();

		if ( loadingDiv ) {
			loadingDiv.style.display = 'none';
		}

		if ( data.result ) {
			displayResults( data.result );

			if ( btnStart ) {
				btnStart.innerHTML = `<span class="dashicons dashicons-update"></span> ${ __(
					'Re-Analyze',
					'md4ai'
				) }`;
			}
		} else if ( resultsDiv ) {
			resultsDiv.innerHTML = `
          <div class="geo-error-message">
            <span class="dashicons dashicons-warning"></span>
            <div>
              <h3>${ __( 'Analysis Failed', 'md4ai' ) }</h3>
              <p>${ __(
					'Unable to complete the analysis. Please try again.',
					'md4ai'
				) }</p>
            </div>
          </div>
        `;
			resultsDiv.style.display = 'block';
		}
	}

	// Add URL input validation
	urlInput?.addEventListener( 'blur', () => {
		const url = urlInput.value.trim();
		if ( url && ! validateUrl( url ) ) {
			showUrlError();
		}
	} );

	btnStart?.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		// Validate URL before proceeding
		if ( urlInput ) {
			const url = urlInput.value.trim();
			if ( ! validateUrl( url ) ) {
				showUrlError();
				return;
			}
		}

		if ( loadingDiv ) {
			loadingDiv.style.display = 'block';
			updateLoadingStep( __( 'Connecting to AI service…', 'md4ai' ) );
		}
		if ( resultsDiv ) {
			resultsDiv.style.display = 'none';
		}

		if ( md4aiData.aiServiceEnabled ) {
			try {
				console.log( 'Waiting for AI services...' );
				waitForAiServices( () =>
					GeoInsightsInit( buildPromptTemplate() )
				);
			} catch ( e ) {
				showErrorAiServices();
			}
		} else {
			showErrorAiServices();
		}
	} );
}
