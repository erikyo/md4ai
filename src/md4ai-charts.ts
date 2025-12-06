import { __ } from '@wordpress/i18n';
import Chart from 'chart.js/auto';
import { Md4aiData, Md4aiStatsResponse } from './types';

// Declare global variables
declare const md4aiData: Md4aiData;

const CHART_COLORS = {
	red: 'rgb(255, 99, 132)',
	orange: 'rgb(255, 159, 64)',
	yellow: 'rgb(255, 205, 86)',
	green: 'rgb(75, 192, 192)',
	blue: 'rgb(54, 162, 235)',
	purple: 'rgb(153, 102, 255)',
	grey: 'rgb(201, 203, 207)',
};

const chartColors = Object.values( CHART_COLORS );

/**
 * Initializes the charts.
 */
export async function md4aiCharts() {
	// Chart data

	const statsEndpoint = md4aiData.restUrl + '/get-stats';
	const visitorsEndpoint = md4aiData.restUrl + '/get-visitors';
	const [ statsResponse, visitorsResponse ] = await Promise.all( [
		fetch( statsEndpoint, {
			headers: { 'X-WP-Nonce': md4aiData.nonce },
		} ).then( ( res ) => res.json() ),

		fetch( visitorsEndpoint, {
			headers: { 'X-WP-Nonce': md4aiData.nonce },
		} ).then( ( res ) => res.json() ),
	] );
	const chartData = ( statsResponse.stats as Md4aiStatsResponse ).chart_data;

	// Requests chart
	const requestsCtx = document.getElementById(
		'md4ai-requests-chart'
	) as HTMLCanvasElement;
	const crawlersCtx = document.getElementById(
		'md4ai-crawlers-chart'
	) as HTMLCanvasElement;

	if ( ! requestsCtx || ! crawlersCtx ) {
		console.error( 'Canvas elements not found!' );
		return;
	}

	if ( requestsCtx ) {
		new Chart( requestsCtx, {
			type: 'line',
			data: {
				labels: chartData.dates,
				datasets: [
					{
						label: __( 'Requests', 'md4ai' ),
						data: chartData.requests_per_day,
						borderColor: '#2271b1',
						backgroundColor: 'rgba(34, 113, 177, 0.1)',
						tension: 0.3,
						fill: true,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: { display: false },
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: { precision: 0 },
					},
				},
			},
		} );
	}

	// Grafico crawler
	if ( crawlersCtx ) {
		new Chart( crawlersCtx, {
			type: 'doughnut',
			data: {
				labels: chartData.crawler_labels,
				datasets: [
					{
						data: chartData.crawler_counts,
						backgroundColor: chartColors,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'right',
					},
				},
			},
		} );
	}

	// --- CHART CONFIGURATION ---
	const trafficData = visitorsResponse.access
		? visitorsResponse.access
		: null;
	console.log( trafficData );

	// --- CHART 3: TRAFFIC SOURCES (Pie) ---
	// Uses data from /get-visitors
	if ( trafficData && trafficData.source_counts ) {
		const sourceLabels = Object.keys( trafficData.source_counts );
		const sourceValues = Object.values( trafficData.source_counts );

		new Chart(
			document.getElementById(
				'md4ai-source-chart'
			) as HTMLCanvasElement,
			{
				type: 'pie',
				data: {
					labels: sourceLabels,
					datasets: [
						{
							label: 'Source Distribution',
							data: sourceValues,
							backgroundColor: chartColors,
							hoverOffset: 4,
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { position: 'right' } },
				},
			}
		);

		// --- CHART 4: REFERRALS PER DAY (Bar) ---
		// Uses data from /get-visitors
		new Chart(
			document.getElementById(
				'md4ai-referrals-chart'
			) as HTMLCanvasElement,
			{
				type: 'bar',
				data: {
					labels: trafficData.referral_chart_data.dates,
					datasets: [
						{
							label: 'Search/LLM Referrals',
							data: trafficData.referral_chart_data.data,
							backgroundColor: chartColors,
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false,
						},
					},
					scales: { y: { beginAtZero: true } },
				},
			}
		);
	}
}
