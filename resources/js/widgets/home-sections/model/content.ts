// Home sahifasi matnlari — markup'dan alohida (o'zgartirish yoki tarjima qilish oson).
import { Clock, ListChecks, Lock } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

export type HowStep = { no: string; title: string; text: string };
export type TrustItem = { icon: LucideIcon; title: string; text: string };

export const HOW_STEPS: HowStep[] = [
    {
        no: '01',
        title: "Rasm va bo'y",
        text: "To'liq bo'yli rasm yuklang, bo'yingiz va odatdagi razmeringizni kiriting. Rasm metama'lumotlardan tozalanadi.",
    },
    {
        no: '02',
        title: 'Uslub va look',
        text: "Classic, Office, Smart Casual yoki Yozgi. Har look'da shim, ko'ylak yoki blazer va aksessuarlar — narxi bilan.",
    },
    {
        no: '03',
        title: 'Natija va tkan',
        text: "O'zingizni yangi kostyumda ko'ring. Yoqmasa — shimni boshqa tkanda qayta ko'ring, rasmni qayta yuklamasdan.",
    },
];

export const TRUST_ITEMS: TrustItem[] = [
    {
        icon: Clock,
        title: "Rasm 24 soatda o'chadi",
        text: 'Yuklangan rasm GPS va metama\'lumotlardan tozalanadi. Istalgan payt "Rasmimni o\'chir" tugmasi bor.',
    },
    {
        icon: Lock,
        title: 'Kalitingiz saqlanmaydi',
        text: "OpenAI kaliti faqat brauzeringizda turadi va so'rov paytida shifrlangan holda ishlatiladi.",
    },
    {
        icon: ListChecks,
        title: "Narx oldindan ko'rinadi",
        text: "Har generatsiyadan oldin taxminiy narx chiqadi. Avtomatik qayta urinish yo'q — ortiqcha to'lov ham yo'q.",
    },
];

/** Tkan vitrinasi: zinapoya effekti uchun har kartaga pastki chekinish (md+). */
export const FABRIC_OFFSETS = ['md:mb-0', 'md:mb-12', 'md:mb-24'];
