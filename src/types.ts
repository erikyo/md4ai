// Window
export interface WP {
  data: {
    select: ( arg: string ) => {
      isServiceAvailable: ( a: string ) => boolean;
      hasAvailableServices: ( a?: { capabilities: string[] } ) => boolean;
      getAvailableService: ( a?: {
        capabilities: string[];
      } ) => boolean | any;
    };
    subscribe: ( callback: () => void, storeName?: string ) => () => void;
  };
}

// Window
export interface Window {
  addEventListener: ( a: string, b: () => void ) => void;
  aiServices: {
    ai: {
      enums: {
        AiCapability: {
          MULTIMODAL_INPUT: string;
          TEXT_GENERATION: string;
        };
      };
      helpers: {
        getTextFromContents: ( arg: string ) => string;
        getCandidateContents: ( arg: string ) => string;
      };
      store: {
        name: string;
      };
    };
  };
}

// Script Data
export interface Md4aiData {
  blogUrl: string;
  restUrl: string;
  nonce: string;
  postId: number;
  aiServiceEnabled: boolean;
  woo_active: boolean;
  prompts: {
    [ key: string ]: string;
  };
}

// Md4ai Stats
export interface Md4aiChartData {
  dates: string[];
  requests_per_day: number[];
  crawler_labels: string[];
  crawler_counts: number[];
}

export interface Md4aiStatsResponse {
  total_requests: number;
  unique_crawlers: number;
  unique_posts: number;
  today_requests: number;
  chart_data: Md4aiChartData;
}

// Geo Insights
export interface GeoInsightsResult {
  scores: {
    identity_match: number;
    tech_match: number;
    ai_perception: number;
  };
  corrections: Array<{
    field: string;
    ai_value: string;
    real_value: string;
    tip: string;
  }>;
  raw_ai_data: {
    score_auth: number;
    score_relevance: number;
    score_data: number;
    score_crawler: number;
    website_name: string;
    author_name: string;
    subject: string;
    main_entity: string;
    is_ecommerce: string;
  };
  ground_truth: {
    website_name: string;
    author_name: string;
    topics: string[];
    is_ecommerce: string;
  };
}
