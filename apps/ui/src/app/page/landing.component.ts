import { Component, OnInit } from '@angular/core';
import {HttpService} from "../shared/http.service";
import 'rxjs/add/operator/map';
import {Response} from "@angular/http";
import {WampService} from "../shared/wamp.service";
import {EventMessage} from "thruway.js/htdocs/thruway.js/src/Messages/EventMessage";
import {ActivatedRoute, ParamMap} from "@angular/router";
import {ToastyService} from "../shared/toasty.service";

interface GlobalMetrics {
    successJobsLastMinute: number,
    successJobsLastHour: number,
    successJobsLastDay: number,
    failedJobsLastHour: number,
}

@Component({
  selector: 'landing',
  template: `
      <div *ngIf="apiBaseUrl" class="row">
          <div class="col-3">
              Connected to:
          </div>
          <div class="col-6">
              {{ this.apiBaseUrl }} (<a routerLink="/settings/base-url">change</a>)
          </div>
      </div>
      <div *ngIf="!apiBaseUrl" class="row">
          <div class="col-6">
              Please <a routerLink="/settings/base-url">connect</a> to Comrade server first:
          </div>
      </div>
      <div *ngIf="globalMetrics" class="row">
          <div class="col-3">
              Success jobs last minute
          </div>
          <div class="col-6">
              {{ globalMetrics.successJobsLastMinute }}
          </div>
      </div>
      <div *ngIf="globalMetrics" class="row">
          <div class="col-3">
              Success jobs last hour:
          </div>
          <div class="col-6">
              {{ globalMetrics.successJobsLastHour }}
          </div>
      </div>
      <div *ngIf="globalMetrics" class="row">
          <div class="col-3">
              Success jobs last day:
          </div>
          <div class="col-6">
              {{ globalMetrics.successJobsLastDay }}
          </div>
      </div>
      <div *ngIf="globalMetrics" class="row">
          <div class="col-3">
              Failed jobs last hour:
          </div>
          <div class="col-6">
              {{ globalMetrics.failedJobsLastHour }}
          </div>
      </div>
      <div *ngIf="apiBaseUrl && !globalMetrics" class="row">
          <div class="col-3">
              Loading statistics
          </div>
      </div>
  `,
})
export class LandingComponent implements OnInit {
  apiBaseUrl: string;

  globalMetrics: GlobalMetrics;

  constructor(
    private httpService: HttpService,
    private wamp: WampService,
    private route: ActivatedRoute,
    private toastyService: ToastyService
  ) {}

  ngOnInit(): void {
      this.route.queryParamMap.subscribe((params: ParamMap) => {
          let url = params.get('apiBaseUrl');
          if (!url) {
              return;
          }

          this.httpService.getInfo(url).subscribe(() => {
              this.httpService.changeApiBaseUrl(url, true);
              this.toastyService.apiBaseUrlForced(url);
          });
      });

      this.httpService.getApiBaseUrlObservable().subscribe((apiBaseUrl) => {
        this.apiBaseUrl = apiBaseUrl;

        this.httpService.get('/api/metrics/global')
          .map((res: Response) => res.json().metrics)
          .subscribe((metrics: GlobalMetrics) => this.globalMetrics = metrics)
        ;
      });

      this.wamp.getWampBaseUrl().subscribe(() => {
        this.wamp.topic('comrade.job_updated')
          .map((event: EventMessage) => event.args[0])
          .switchMap(() => this.httpService.get('/api/metrics/global'))
          .map((res: Response) => res.json().metrics)
          .subscribe((metrics: GlobalMetrics) => this.globalMetrics = metrics)
        ;
      });
  }
}
