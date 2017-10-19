import {Moment} from "moment/moment";

export class Date {
    schema: string = 'http://comrade.forma-pro.com/schemas/date.json';
    unix: number;
    iso: string;

    static fromMoment(moment: Moment):Date
    {
        let date = new Date();
        date.unix = parseInt(moment.format('X'));
        date.iso = moment.toISOString();

        return date;
    }
}
