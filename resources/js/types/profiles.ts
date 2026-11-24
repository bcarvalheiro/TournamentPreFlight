/** One of the account's own players, as App\Data\Private\OwnProfileData sends it. */
export type OwnProfile = {
    id: number;
    name: string;
    popId: string;
    birthYear: number;
    isChild: boolean;
};
