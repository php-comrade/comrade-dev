import { Component, OnInit } from '@angular/core';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/filter';
import {HttpService} from "../shared/http.service";
import {Observable} from "rxjs/Observable";
import 'rxjs/add/observable/of';
import {ActivatedRoute, Router} from "@angular/router";
import {ToastyService} from "../shared/toasty.service";

@Component({
  selector: 'api-base-url',
  template: `
      <div class="form-group row">
          <div class="col-md-2">
              <p class="form-control-static">Api base url:</p>
          </div>
          <div class="col-md-5">
              <input
                  class="form-control"
                  id="api-base-url"
                  type="url"
                  [ngClass]="{'ng-valid': serverInfo, 'ng-invalid': serverInfo === false}"
                  required
                  (input)="resetResult()"
                  (focus)="resetResult()"
                  (keypress)="onKeyPressed($event, baseUrl.value)"
                  [value]="apiBaseUrl" #baseUrl
              >
          </div>
          <div class="col-md-3">
              <button *ngIf="!serverInfo" class="btn btn-default btn-success" [ngClass]="{'btn-success': serverInfo, 'btn-danger': serverInfo === false}" (click)="testBaseUrl(baseUrl.value)">Test</button>
              <button *ngIf="serverInfo" class="btn btn-default btn-success" (click)="useBaseUrl(baseUrl.value, true)">Store & Use</button>
              <button *ngIf="serverInfo" class="btn btn-default btn-success" (click)="useBaseUrl(baseUrl.value, false)">Use</button>
          </div>
      </div>

      <prettyjson *ngIf="serverInfo" [obj]="serverInfo"></prettyjson>
  `,
})
export class ApiBaseUrlComponent implements OnInit {
    apiBaseUrl: string = '';

    serverInfo: any;

    constructor(private httpService: HttpService, private router: Router, private route: ActivatedRoute, private toastyService: ToastyService) {}

    ngOnInit(): void {
      let apiBaseUrl = this.httpService.getApiBaseUrl();
      if (typeof apiBaseUrl !== 'undefined') {
        this.apiBaseUrl = apiBaseUrl;
      }

      this.testBaseUrl(this.apiBaseUrl);
    }

    resetResult(): void {
        this.serverInfo = null;
    }

    testBaseUrl(apiBaseUrl: string): void {
        if (!apiBaseUrl) {
            this.serverInfo = null;

            return;
        }

        this.httpService.getInfo(apiBaseUrl)
            .catch(err => Observable.throw(err))
            .subscribe(
                res => {
                  this.serverInfo = res.json();
                },
                err => {
                    this.serverInfo = false;
                }
            )
        ;
    }

    useBaseUrl(apiBaseUrl: string, store: boolean): void {
        this.httpService.changeApiBaseUrl(apiBaseUrl, store);

        this.router.navigate(['']);
    }

    onKeyPressed(event: KeyboardEvent, apiBaseUrl: string) {
       if (event.code == 'Enter' && apiBaseUrl) {
        this.testBaseUrl(apiBaseUrl);
      }
    }
}
