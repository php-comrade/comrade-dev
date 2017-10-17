import {Component, OnInit} from '@angular/core';
import 'rxjs/add/observable/interval';
import 'rxjs/add/operator/map';
import {ErrorService} from "../shared/error.service";
import {ServerError} from "../shared/server-error";
import {Date as MyDate} from "../shared/date";
import {Observable} from "rxjs/Observable";
import {Title} from "@angular/platform-browser";
import {WampService} from "../shared/wamp.service";
import {EventMessage} from "thruway.js/src/Messages/EventMessage";


@Component({
  selector: 'late-server-errors',
  templateUrl: './late-server-errors.component.html',
})

export class LateServerErrorsComponent implements OnInit {
    error: string;

    lateErrors: ServerError[] = [];

    lastMinuteErrors: ServerError[] = [];

    lastFiveMinutesErrors: ServerError[]  = [];

    lastHourErrors: ServerError[]  = [];

    olderErrors: ServerError[] = [];

    reorderedAt: number;

    triggerRaw: boolean[] = [];
    triggerErrorRaw: boolean[] = [];
    triggerRequestRaw: boolean[] = [];
    triggerQueueRaw: boolean[] = [];

    constructor(
        private errorService: ErrorService,
        private titleService: Title,
        private wamp: WampService,
    ) { }

    ngOnInit(): void {
        this.wamp.topic('job_manager.internal_error')
            .map((e: EventMessage) => e.args[0] as ServerError)
            .subscribe((error: ServerError) => {
                error.createdAtAsDate = this.convertMicroTimeToDate(error.createdAt);

                this.lateErrors = [...this.lateErrors, error];
                this.reorderErrors();
            })
        ;

        this.titleService.setTitle('Comrade - Server errors');

        this.refresh();

        Observable.interval(2000).subscribe(() => this.reorderErrors());
    }

    reorderErrors(): void {
        const now: number = parseInt((Date.now() / 1000).toString());
        const oneMinuteAgo = now - 60;
        const fiveMinutesAgo = now - 300;
        const hourAgo = now - 3600;

        this.lastMinuteErrors = this.lateErrors.filter((error: ServerError) => error.createdAtAsDate.unix >= oneMinuteAgo);
        this.lastFiveMinutesErrors = this.lateErrors.filter((error: ServerError) => {
            return error.createdAtAsDate.unix < oneMinuteAgo && error.createdAtAsDate.unix >= fiveMinutesAgo;
        });
        this.lastHourErrors = this.lateErrors.filter((error: ServerError) => {
            return error.createdAtAsDate.unix < fiveMinutesAgo && error.createdAtAsDate.unix >= hourAgo;
        });
        this.olderErrors = this.lateErrors.filter((error: ServerError) => {
            return error.createdAtAsDate.unix < hourAgo;
        });

        this.reorderedAt = now;
    }

    convertMicroTimeToDate(microTime: number): MyDate {
        let date = new MyDate();
        date.unix = parseInt((microTime / 1000000).toString());

        return date;
    }

    refresh():void {
        this.resetErrors();

        this.errorService.getLateErrors().subscribe((errors: ServerError[]) => {
            this.lateErrors = errors.map((error: ServerError) => {
                error.createdAtAsDate = this.convertMicroTimeToDate(error.createdAt);

                return error;
            });

            this.reorderErrors();
        });
    }

    deleteOlder(olderSeconds: number):void {
        this.errorService.deleteOlderThan(Date.now() - (olderSeconds * 1000)).subscribe(
        () => this.refresh(),
        err => this.error = err
        );
    }

    deleteAll():void {
        this.errorService.deleteAll().subscribe(
            () => this.refresh(),
            err => this.error = err
        );
    }

    resetErrors(): void {
        this.lateErrors = null;
        this.lastMinuteErrors = [];
        this.lastFiveMinutesErrors = [];
        this.lastHourErrors = [];
        this.olderErrors = [];
    }
}
