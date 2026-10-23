// English UI messages (default locale). Add another locale as a file with the same shape
// (`satisfies MessageSchema` in its file makes the compiler check every key).
export const en = {
    common: {
        uzs: '{amount} UZS',
    },
    nav: {
        main: 'Main menu',
        howItWorks: 'How it works',
        styles: 'Styles',
        fabrics: 'Fabrics',
        trust: 'Privacy',
        tryOn: 'Try it on',
    },
    home: {
        metaTitle: 'See the suit on you before you buy',
        hero: {
            eyebrow: "Virtual fitting · men's tailoring",
            title: 'See the suit on yourself — before you buy it.',
            lead: 'Upload your photo and pick a style. Trousers, shirt and accessories are fitted to your height — in any fabric you like.',
            cta: 'Try it on',
            how: 'How it works',
            estimate:
                'One look ≈ {estimate} with your own OpenAI key — or a free demo',
        },
        how: {
            title: "Three steps and you're in front of the mirror.",
            lead: 'Compare cut, colour and fabric on your own photo before visiting the shop. Each result takes about 20–40 seconds.',
            steps: [
                {
                    title: 'Photo and height',
                    text: 'Upload a full-length photo and enter your height and usual size. Metadata is stripped from the photo.',
                },
                {
                    title: 'Style and look',
                    text: 'Classic, Office, Smart Casual or Summer. Each look has trousers, a shirt or blazer and accessories — with prices.',
                },
                {
                    title: 'Result and fabric',
                    text: 'See yourself in the new outfit. Not sure? Try the trousers in another fabric without uploading again.',
                },
            ],
        },
        styles: {
            title: 'Choose a style',
            allLooks: 'All looks →',
            looks: '{count} look | {count} looks',
            from: 'from {price}',
            coverAlt: 'Suit in the {name} style',
        },
        fabrics: {
            eyebrow: 'One cut · any fabric',
            title: 'See the same cut in another fabric.',
            lead: 'Tap a fabric on the result screen — your photo is not uploaded again, only the trousers (or the whole suit) are re-tailored. The price is shown up front.',
            alt: '{name} fabric',
        },
        trust: {
            items: [
                {
                    title: 'Photos are deleted within 24 hours',
                    text: 'Uploaded photos are stripped of GPS and metadata. A "Delete my photo" button is always there.',
                },
                {
                    title: 'Your key is never stored',
                    text: 'Your OpenAI key stays in your browser and is only used, encrypted, for the request itself.',
                },
                {
                    title: 'The price is shown up front',
                    text: 'Every generation shows its estimated cost first. No automatic retries — no surprise charges.',
                },
            ],
        },
        cta: { title: 'The mirror is ready. Your turn.' },
        footer: {
            copyright: '© 2026 Boutique·AI — demo project',
            credits:
                'Photos: Unsplash · Video: Pexels · Catalogue: AI-generated',
        },
    },
    tryOn: {
        metaTitle: 'Try it on',
        steps: {
            label: 'Steps',
            setup: '1 · You & look',
            waiting: '2 · Tailoring',
            result: '3 · Result',
        },
        apiKey: {
            title: 'Your OpenAI API key',
            lead: 'Generation runs on your own key (one look ≈ {estimate}). The key stays in this browser and is never stored on our server. Tip: create a separate OpenAI project with a monthly limit.',
            demoTitle: 'Demo mode — free',
            demoLead:
                'Without a key you get a simulated preview. Add your own OpenAI key (one look ≈ {estimate}) to get a real AI try-on. The key stays in this browser only.',
            addKey: 'Use my OpenAI key',
            label: 'API key',
            save: 'Save',
            remember:
                'Remember on this device (otherwise it is cleared when the tab closes)',
            badgeDemo: 'Demo mode · no key needed',
            badgeForget: 'remove',
        },
        you: {
            title: 'You',
            waist: 'Waist (cm), optional',
            consentAi:
                'I agree to my photo being sent to OpenAI and billed to my own key. The photo is deleted within 24 hours.',
            consentDemo:
                'I agree to my photo being processed for this preview. The photo is deleted within 24 hours.',
        },
        photo: {
            alt: 'Your uploaded photo',
            privacy: 'GPS and metadata are removed on upload',
            replace: 'Another photo',
            empty: 'Upload a photo or drop it here',
            hint: 'Full length, facing the camera, in good light. {formats}, up to {mb} MB.',
            inputLabel: 'Choose a photo',
            or: 'or',
            wrongType: 'Only {formats} photos are supported.',
            tooLarge: 'The photo must be under {mb} MB.',
        },
        height: {
            label: 'Height',
            decrease: 'Decrease height by 1 cm',
            increase: 'Increase height by 1 cm',
            value: '{cm} cm',
            valueText: '{cm} centimetres',
            hint: 'Drag the tape or use the ← → keys.',
        },
        size: { label: 'Usual size' },
        quality: { medium: 'Medium', high: 'High' },
        look: {
            title: 'Style & look',
            styles: 'Styles',
            selected: 'Selected',
            suit: 'Suit',
        },
        fabric: {
            trousers: 'Trousers fabric',
            suit: 'Suit fabric',
            piecesTrousers: 'the trousers are',
            piecesSuit: 'the jacket and trousers are',
            noSurcharge: 'no extra cost',
            surcharge: '+{amount} UZS',
        },
        submit: {
            look: 'Look',
            size: 'Size',
            quality: 'Quality',
            estimate: '≈ {estimate}',
            demo: 'Demo · free',
            submitting: 'Sending…',
            submit: 'Dress me →',
        },
        waiting: {
            eyebrow: 'AI is tailoring · {summary}',
            queued: 'In the queue…',
            messages: [
                'Preparing your photo…',
                'Fitting the cut…',
                'Laying out the fabric…',
                'Sewing the seams…',
                'Matching the light…',
            ],
            steps: [
                'Photo cleaned and resized',
                'Look and fabric prepared',
                'Tailoring the outfit',
                'Result',
            ],
            stop: 'Stop waiting',
            stopNote:
                'The request has already been sent to the AI — it may still be billed if you stop. Your photo will be deleted.',
        },
        result: {
            label: 'Result',
            lookTotal: 'Look total',
            suggestedSize: 'Suggested size',
            measurements: 'height {height} cm',
            measurementsWaist: 'height {height} cm · waist {waist} cm',
            spent: 'Spent: {cost}',
            swapTitle: '{label}: try another',
            swapButton: 'See it in this fabric · ≈ {estimate}',
            swapNote:
                'Your photo is not uploaded again — only {pieces} re-tailored in the new fabric.',
            download: 'Download',
            anotherLook: 'Another look',
            deletePhoto: 'Delete my photo now',
            demoNote:
                'Demo result (simulated). Add your OpenAI key for a real AI try-on.',
        },
        compare: {
            before: 'Before',
            after: 'After',
            beforeAlt: 'Before: your photo',
            afterAlt: 'After: the selected look',
            slider: 'Compare',
            disclaimer:
                'AI image. Real colour and pattern may differ slightly — check the fabric sample in the shop.',
        },
        errors: {
            network: 'No connection to the server. Please check your internet.',
            notFound:
                'Request not found — your session may have expired. Please start again.',
            expired: 'This page has expired. Please refresh it.',
            tooMany: 'Too many requests. Please try again in a minute.',
            generic: 'Something went wrong. Please try again.',
        },
    },
};

export type MessageSchema = typeof en;
