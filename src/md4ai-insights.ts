// Declare global variables
import {generateAiText, waitForAiServices} from './md4ai-services';
import {GeoInsightsResult, Md4aiData} from "./types";
import {__} from "@wordpress/i18n";

declare const md4aiData: Md4aiData;

/**
 * Updates the gauge chart with the given percentage.
 * @param selector The chart selector
 * @param percentage The percentage to display
 */
function updateGaugeChart(selector: string, percentage: number) {
  const chart = document.querySelector(selector);
  if (!chart) {
    return;
  }

  const circle = chart.querySelector('.circle') as SVGPathElement;
  const text = chart.querySelector('.percentage') as SVGTextElement;

  if (circle && text) {
    circle.setAttribute('stroke-dasharray', `${percentage}, 100`);
    text.textContent = percentage.toString();
  }
}

/**
 * Returns the color for the given score.
 * @param score The score to get the color for
 * @returns The color for the score
 */
function getScoreColor(score: number): string {
  if (score >= 90) {
    return 'green';
  }
  if (score >= 50) {
    return 'orange';
  }
  return 'red';
}

/**
 * Creates a suggestion box for the given corrections.
 * @param corrections The corrections to display
 * @returns The suggestion box HTML
 */
function createSuggestionBox(
  corrections: GeoInsightsResult[ 'corrections' ]
): string {
  if (!corrections || corrections.length === 0) {
    return `
      <div class="geo-suggestion-box geo-success">
        <div class="suggestion-header">
          <span class="suggestion-icon">✓</span>
          <h3>${__( 'All Checks Passed', 'md4ai' )}</h3>
        </div>
        <p>${__( 'Your website\'s identity is properly configured for AI systems.', 'md4ai' )}</p>
      </div>
    `;
  }

  const suggestionItems = corrections
    .map(
      (correction) => `
    <div class="suggestion-item">
      <div class="suggestion-title">
        <span class="suggestion-bullet">▸</span>
        <strong>${correction.field}</strong>
      </div>
      <div class="suggestion-details">
        <div class="suggestion-row">
          <span class="label">AI Detected:</span>
          <span class="value ai-value">${correction.ai_value.substring(
        0,
        100
      )}${correction.ai_value.length > 100 ? '...' : ''}</span>
        </div>
        <div class="suggestion-row">
          <span class="label">${__( 'Expected:', 'md4ai' )}</span>
          <span class="value expected-value">${correction.real_value}</span>
        </div>
        <div class="suggestion-tip">
          <span class="tip-icon">💡</span>
          ${correction.tip}
        </div>
      </div>
    </div>
  `
    )
    .join('');

  return `
    <div class="geo-suggestion-box geo-warning">
      <div class="suggestion-header">
        <span class="suggestion-icon">⚠</span>
        <h3>${__( 'Opportunities for Improvement', 'md4ai' )}</h3>
      </div>
      <p class="suggestion-intro">${__( 'AI systems detected the following discrepancies. Fixing these will improve your site\'s visibility:', 'md4ai' )}</p>
      <div class="suggestion-list">
        ${suggestionItems}
      </div>
    </div>
  `;
}

/**
 * Displays the results in the results div.
 * @param data The results data
 */
function displayResults(data: GeoInsightsResult) {
  const resultsDiv = document.getElementById('geo-results');
  if (!resultsDiv) {
    return;
  }

  // Update gauge charts
  updateGaugeChart(
    '.circular-chart.orange',
    data.raw_ai_data.score_auth * 10
  );
  updateGaugeChart(
    '.circular-chart.green',
    data.raw_ai_data.score_relevance * 10
  );
  updateGaugeChart(
    '.circular-chart.blue',
    data.raw_ai_data.score_data * 10
  );
  updateGaugeChart(
    '.circular-chart.purple',
    data.raw_ai_data.score_crawler * 10
  );

  // Update gauge colors based on scores
  const authScore = data.raw_ai_data.score_auth * 10;
  const relevanceScore = data.raw_ai_data.score_relevance * 10;
  const dataScore = data.raw_ai_data.score_data * 10;
  const crawlerScore = data.raw_ai_data.score_crawler * 10;

  // Create overall score section
  const avgScore = Math.round(
    (authScore + relevanceScore + dataScore + crawlerScore) / 4
  );
  const scoreColor = getScoreColor(avgScore);

  const overallScoreHTML = `
    <div class="geo-overall-score">
      <div class="score-circle ${scoreColor}">
        <span class="score-number">${avgScore}</span>
      </div>
      <div class="score-info">
        <h2>AI Perception Score</h2>
        <p>How well AI systems understand your website</p>
      </div>
    </div>
  `;

  // Create metrics breakdown
  const metricsHTML = `
    <div class="geo-metrics-breakdown">
      <h3>Detailed Metrics</h3>
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-label">Identity Match</div>
          <div class="metric-value">${data.scores.identity_match}%</div>
          <div class="metric-bar">
            <div class="metric-bar-fill" style="width: ${data.scores.identity_match}%"></div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Technical Match</div>
          <div class="metric-value">${data.scores.tech_match}%</div>
          <div class="metric-bar">
            <div class="metric-bar-fill" style="width: ${data.scores.tech_match}%"></div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-label">AI Perception</div>
          <div class="metric-value">${data.scores.ai_perception}%</div>
          <div class="metric-bar">
            <div class="metric-bar-fill" style="width: ${data.scores.ai_perception}%"></div>
          </div>
        </div>
      </div>
    </div>
  `;

  // Create suggestions box
  const suggestionsHTML = createSuggestionBox(data.corrections);

  // Create detailed report
  const reportHTML = `
    <div class="geo-detailed-report">
      <h3>AI Knowledge Analysis</h3>
      <div class="report-grid">
        <div class="report-item">
          <span class="report-label">Website Name:</span>
          <span class="report-value">${data.ground_truth.website_name}</span>
        </div>
        <div class="report-item">
          <span class="report-label">Author:</span>
          <span class="report-value">${data.ground_truth.author_name}</span>
        </div>
        <div class="report-item">
          <span class="report-label">Topics:</span>
          <span class="report-value">${data.ground_truth.topics.join(
    ', '
  )}</span>
        </div>
        <div class="report-item">
          <span class="report-label">E-Commerce:</span>
          <span class="report-value">${data.ground_truth.is_ecommerce}</span>
        </div>
      </div>
    </div>
  `;

  // Update the results div
  resultsDiv.innerHTML = `
    ${overallScoreHTML}
    <div class="geo-scores-container">
      ${resultsDiv.querySelector('.geo-scores-container')?.outerHTML || ''}
    </div>
    ${metricsHTML}
    ${suggestionsHTML}
    ${reportHTML}
  `;

  resultsDiv.style.display = 'block';
}

/**
 * Builds the prompt template for the insights.
 * @returns The prompt template
 */
function buildPromptTemplate() {
  let dynamicSection = '';
  const isWooActive = md4aiData.woo_active;

  if (isWooActive) {
    dynamicSection = `4. E-COMMERCE DETECTION
Is an E-commerce site: [Yes / No]
Reasoning for Detection: [Complete]
6. PRODUCT CATALOG
Main Product Categories: [List 3-5]
Specific Products Known: [List 3 SKUs]
Estimated Best Sellers: [Complete]`;
  }

  const promptTemplate = `Act as a Senior AI Search Engineer and SEO Specialist. Your task is to analyze your internal knowledge base regarding the following domain: ${md4aiData.blogUrl}
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
${dynamicSection}
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
  const btnStart = document.getElementById('btn-start-analysis');
  const loadingDiv = document.getElementById('geo-loading');
  const resultsDiv = document.getElementById('geo-results');


  /**
   * Show error message when AI Services is not available
   */
  function showErrorAiServices() {
    console.error('Error: Please install Ai services in order to enable GEO insights')
    btnStart.innerHTML = 'Please, install AI-Services plugin';
    btnStart.classList.add('disabled');
    resultsDiv.innerHTML = `<div class="error" style="margin:20px 0;padding:12px;display:flex;gap:10px;align-items:center;justify-content:space-between;">Please, install AI-Services plugin to analyze your site <a href="${md4aiData.blogUrl}/wp-admin/plugin-install.php?s=AI%2520Services&tab=search&type=term" target="_blank" class="button button-primary md4ai-button">Download</a></div>`;
    resultsDiv.style.display = 'block';
    loadingDiv!.style.display = 'none';
  }

  /**
   * Initializes Geo Insights analysis.
   * @param {string} promptTemplate - The AI prompt to generate text.
   * @returns {Promise<void>} - A promise that resolves when the analysis is complete.
   */
  async function GeoInsightsInit(promptTemplate: string): Promise<void> {

    const generated = await generateAiText(promptTemplate);
    console.log(generated);

    const response = await fetch(
      md4aiData.restUrl + '/geo-insights',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': md4aiData.nonce,
        },
        body: JSON.stringify({
          content: generated,
        }),
      }
    );

    const data = await response.json();

    loadingDiv!.style.display = 'none';

    if (data.result) {
      displayResults(data.result);

      btnStart.innerHTML = 'Re-Analyze';
    } else {
      resultsDiv!.innerHTML =
        '<div class="error">Unavailable</div>';
      resultsDiv!.style.display = 'block';
    }
  }

  btnStart?.addEventListener('click', (e) => {
    e.preventDefault();
    loadingDiv!.style.display = 'block';
    resultsDiv!.style.display = 'none';

    if (md4aiData.aiServiceEnabled) {
      try {
        console.log('Waiting for AI services...');
        waitForAiServices(
          () => GeoInsightsInit(buildPromptTemplate())
        );
      } catch (e) {
        showErrorAiServices()
      }
    } else {
      showErrorAiServices()
    }
  });
}
