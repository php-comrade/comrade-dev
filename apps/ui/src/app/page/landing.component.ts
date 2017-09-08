import { Component, OnInit } from '@angular/core';
import {HttpService} from "../shared/http.service";
import 'rxjs/add/operator/map';
import {Response} from "@angular/http";
import {WampService} from "../shared/wamp.service";
import {EventMessage} from "thruway.js/htdocs/thruway.js/src/Messages/EventMessage";

interface GlobalMetrics {
    successJobsLastMinute: number,
    successJobsLastHour: number,
    successJobsLastDay: number,
    failedJobsLastHour: number,
}

@Component({
  selector: 'landing',
  template: `
      <div class="row">
          <div class="col-3">
              Connected to:
          </div>
          <div class="col-6">
              {{ this.apiBaseUrl }} (<a routerLink="/settings/base-url">change</a>)
          </div>
      </div>
      <div class="row">
          <div class="col-3">
              Success jobs last minute:
          </div>
          <div class="col-6">
              {{ globalMetrics.successJobsLastMinute }}
          </div>
      </div>
      <div class="row">
          <div class="col-3">
              Success jobs last hour:
          </div>
          <div class="col-6">
              {{ globalMetrics.successJobsLastHour }}
          </div>
      </div>
      <div class="row">
          <div class="col-3">
              Success jobs last day:
          </div>
          <div class="col-6">
              {{ globalMetrics.successJobsLastDay }}
          </div>
      </div>
      <div class="row">
          <div class="col-3">
              Failed jobs last hour:
          </div>
          <div class="col-6">
              {{ globalMetrics.failedJobsLastHour }}
          </div>
      </div>
  `,
})
export class LandingComponent implements OnInit {
  private apiBaseUrl: string;

  private globalMetrics: GlobalMetrics;

  constructor(private httpService: HttpService, private wamp: WampService) {}

  ngOnInit(): void {
      this.apiBaseUrl = this.httpService.getApiBaseUrl();

      this.httpService.get('/api/metrics/global')
          .map((res: Response) => res.json().metrics)
          .subscribe((metrics: GlobalMetrics) => this.globalMetrics = metrics)
      ;

      this.wamp.topic('job_manager.update_job')
          .map((event: EventMessage) => event.args[0])
          .switchMap(() => this.httpService.get('/api/metrics/global'))
          .map((res: Response) => res.json().metrics)
          .subscribe((metrics: GlobalMetrics) => this.globalMetrics = metrics)
      ;
  }
}
