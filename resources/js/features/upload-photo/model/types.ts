/** Backend'dagi limitlar (config/tryon.php → TryOnController::create props.upload). */
export type UploadLimits = {
    allowed_mimes: string[];
    max_kb: number;
};
