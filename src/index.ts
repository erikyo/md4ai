/**
 * Initializes the MD4AI admin functionality on DOM load.
 */
import { md4ai_markdown } from './md4ai-markdown';
import { md4aiCharts } from './md4ai-charts';
import { handleMd4aiButtons } from './md4ai-admin';
import { initGeoInsights } from './md4ai-insights';

document.addEventListener( 'DOMContentLoaded', () => {
  // Admin
	if ( document.querySelector( '.md4ai-admin' ) || document.getElementById( 'md4ai_metabox' ) ) {
		md4ai_markdown();
		handleMd4aiButtons();
	}

  // Charts
	if ( document.querySelector( '.md4ai-charts-container' ) ) {
		md4aiCharts();
	}

  // Geo Insights
	if ( document.querySelector( '.geo-insights-wrapper' ) ) {
		initGeoInsights();
	}
} );
