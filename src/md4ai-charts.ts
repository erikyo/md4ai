import { __ } from '@wordpress/i18n';
import Chart from 'chart.js/auto';
import {Md4aiData, Md4aiStatsResponse} from "./types";

// Declare global variables
declare const md4aiData: Md4aiData;


/**
 * Initializes the charts.
 */
export async function md4aiCharts() {
	// Chart data
	const response = await fetch( md4aiData.restUrl + '/get-stats', {
		method: 'GET',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': md4aiData.nonce,
		},
	} ).then( ( response ) => response.json() );
	const chartData = ( response.stats as Md4aiStatsResponse ).chart_data;

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
						backgroundColor: [
							'#2271b1',
							'#00a32a',
							'#d63638',
							'#f0a800',
							'#8c8f94',
						],
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
}
