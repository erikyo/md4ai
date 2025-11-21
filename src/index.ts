/**
 * Initializes the MD4AI admin functionality on DOM load.
 */
import {md4ai_markdown} from "./md4ai-markdown";
import {md4aiCharts} from "./md4ai-charts";
import {handleMd4aiButtons} from "./md4ai-admin";


document.addEventListener( 'DOMContentLoaded', () => {
  const isAdminPage = document.querySelector( '.md4ai-admin' ) !== null;
  const isMetabox = document.getElementById( 'md4ai_metabox' ) !== null;

  if ( isAdminPage || isMetabox ) {
    md4ai_markdown();
    handleMd4aiButtons();
  }

  const is_dashboard = document.querySelector( '.md4ai-charts-container' );
  if ( is_dashboard ) {
    md4aiCharts();
  }
} );
