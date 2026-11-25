/** One of the account's own players, as App\Data\Private\OwnProfileData sends it. */
export type OwnProfile = {
    id: number;
    name: string;
    popId: string;
    birthYear: number;
    isChild: boolean;
};

/**
 * The account's own trainers and answers on the home page, as
 * App\Data\Private\OwnAttendanceData sends it (`own` prop; null for guests).
 * `going` maps an event id to its trainers going; read it as `going[id] ?? []`
 * (an empty map arrives as `[]`).
 */
export type OwnAttendance = {
    trainers: { id: number; name: string }[];
    going: Record<number, number[]>;
};
