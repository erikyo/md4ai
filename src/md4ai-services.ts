const { enums, helpers, store: aiStore } = window.aiServices.ai;
const SERVICE_ARGS = { capabilities: [ enums.AiCapability.TEXT_GENERATION ] };

declare const wp: {
  data: {
    select: (arg: string) => {
      isServiceAvailable: (a: string) => boolean;
      hasAvailableServices: (a?: { capabilities: string[] }) => boolean;
      getAvailableService: (a?: { capabilities: string[] }) => boolean;
    };
  };
};

declare const window: {
  addEventListener: (a:string, b: () => void) => void;
  aiServices: {
    ai: {
      enums: {
        AiCapability: {
          MULTIMODAL_INPUT: string;
          TEXT_GENERATION: string;
        };
      };
      helpers: any;
      store: any;
    };
  };
};

async function runAiLogic() {
  const { select } = wp.data;
  const { getAvailableService } = select('ai-services/ai');

  // BUG FIX: Pass SERVICE_ARGS to getAvailableService
  const service = getAvailableService( SERVICE_ARGS );

  if (!service) {
    // This can happen if hasAvailableServices is true but getAvailableService fails
    alert('Failed to get an AI service instance.');
    return;
  }

  try {
    const candidates = await service.generateText(
      'What is the Generative Engine Optimization?',
      { feature: 'my-test-feature' }
    );

    const text = helpers.getTextFromContents(
      helpers.getCandidateContents(candidates)
    );

    alert(text);
  } catch (error) {
    // Show the error message, not just [object Object]
    alert(error.message || error);
  }
}

// This function sets up the subscription
function waitForAiServices() {
  const { select } = wp.data;
  const { hasAvailableServices } = select( 'ai-services/ai' );
  if ( hasAvailableServices( SERVICE_ARGS ) ) {
    try {
      return runAiLogic();
    } catch ( error ) {
      alert(error);
    }
  } else {
    setTimeout(waitForAiServices, 100);
  }
}

// Run the subscription setup function on window load
if (typeof window) {
  waitForAiServices()
} else {
  window!.addEventListener('load', waitForAiServices);
}

// You can still export the main logic if needed
export default runAiLogic;
