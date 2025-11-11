declare const wp: {
  data: {
    select: (arg: string) => {
      isServiceAvailable: (a: string) => boolean;
      hasAvailableServices: (a?: { capabilities: string[] }) => boolean;
      getAvailableService: (a?: { capabilities: string[] }) => boolean;
    };
    subscribe: (callback: () => void, storeName?: string) => () => void;
  };
};

declare const window: {
  addEventListener: (a: string, b: () => void) => void;
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

const {enums, helpers, store: aiStore} = window.aiServices.ai;
const SERVICE_ARGS = {capabilities: [enums.AiCapability.TEXT_GENERATION]};

/**
 * Wait for AI services to be available and run the AI logic.
 */
function waitForAiServices() {
  const {select, subscribe} = wp.data;
  function checkAndRun() {
    try {
      const {hasAvailableServices} = select(aiStore.name);

      if (hasAvailableServices(SERVICE_ARGS)) {
        /** ready */
        return true;
      }
    } catch (error) {
      console.error(error);
      return false;
    }
    return false;
  }

  // Try immediately first
  if (checkAndRun()) {
    try {
      runAiLogic();
    } catch (error) {
      alert(error);
    }
    return;
  }

  // If not available, subscribe to changes
  const unsubscribe = subscribe(() => {
    if (checkAndRun()) {
      unsubscribe();
      try {
        runAiLogic();
      } catch (error) {
        alert(error);
      }
    }
  });
}

/**
 * Run AI logic.
 */
async function runAiLogic() {
  const {select} = wp.data;
  const {getAvailableService} = select('ai-services/ai');

  const service = getAvailableService(SERVICE_ARGS) as false | {
    generateText: (arg: string, arg2: {feature: string}) => Promise<any>;
  };

  if (!service) {
    alert('Failed to get an AI service instance.');
    return;
  }

  try {
    const candidates = await service.generateText(
      'What is the Generative Engine Optimization?',
      {feature: 'my-test-feature'}
    );

    const text = helpers.getTextFromContents(
      helpers.getCandidateContents(candidates)
    );

    alert(text);
  } catch (error) {
    alert(error.message || error);
  }
}

/**
 * Run on DOMContentLoaded or immediately if DOM is ready
 */
if (document.readyState === 'loading') {
  // document.addEventListener('DOMContentLoaded', waitForAiServices);
} else {
  // waitForAiServices();
}

export {runAiLogic, waitForAiServices};
