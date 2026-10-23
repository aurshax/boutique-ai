/**
 * How the try-on uses the visitor's OpenAI key (TryOnController::create → keyMode):
 *  - required — real AI only, the visitor must enter a key;
 *  - optional — public demo: free simulated result, or real AI with the visitor's own key;
 *  - none     — development mock, no key at all.
 */
export type KeyMode = 'required' | 'optional' | 'none';
