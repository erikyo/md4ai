import { __ } from '@wordpress/i18n';
import Chart from 'chart.js/auto';
export interface Md4aiData {
	restUrl: string;
	nonce: string;
}

// Declare global variables
export declare const md4aiData: Md4aiData;

interface Md4aiChartData {
	dates: string[];
	requests_per_day: number[];
	crawler_labels: string[];
	crawler_counts: number[];
}

interface Md4aiStatsResponse {
	total_requests: number;
	unique_crawlers: number;
	unique_posts: number;
	today_requests: number;
	chart_data: Md4aiChartData;
}

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
		const lineChart = new Chart( requestsCtx, {
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
		const doughnutChart = new Chart( crawlersCtx, {
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
