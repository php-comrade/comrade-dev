import { Component, OnInit } from '@angular/core';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/filter';
import {HttpService} from "../shared/http.service";
import {Observable} from "rxjs/Observable";
import 'rxjs/add/observable/of';
import {Router} from "@angular/router";
import {LocalStorageService} from "ngx-webstorage";

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
                  [ngClass]="{'ng-valid': serverInfo, 'ng-invalid': serverInfo === false, 'disabled': checking}"
                  required
                  [disabled]="checking"
                  (input)="resetResult()"
                  (focus)="resetResult()"
                  [value]="apiBaseUrl" #baseUrl
              >
          </div>
          <div class="col-md-3">
              <button *ngIf="!serverInfo" class="btn btn-default btn-success" [disabled]="checking" [ngClass]="{'btn-success': serverInfo, 'btn-danger': serverInfo === false}" (click)="testBaseUrl(baseUrl.value)">Test</button>
              <button *ngIf="serverInfo" class="btn btn-default btn-success" (click)="useBaseUrl(baseUrl.value, true)">Store & Use</button>
              <button *ngIf="serverInfo" class="btn btn-default btn-success" (click)="useBaseUrl(baseUrl.value, false)">Use</button>
          </div>
      </div>

      <prettyjson *ngIf="serverInfo" [obj]="serverInfo"></prettyjson>
  `,
})
export class ApiHealthcheckComponent implements OnInit {
    private apiBaseUrl: string;

    private serverInfo: any;

    private checking: boolean;

    constructor(private httpService: HttpService, private router: Router) {}

    ngOnInit(): void {
        this.checking = false;

        this.apiBaseUrl = this.httpService.getApiBaseUrl();
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

        this.checking = true;

        this.httpService.getInfo(apiBaseUrl)
            .catch(err => Observable.throw(err))
            .subscribe(
                res => {
                    this.serverInfo = res.json();
                    // this.checking = false;
                },
                () => {
                    this.serverInfo = false;
                    // this.checking = false;
                }
            )
        ;
    }

    useBaseUrl(apiBaseUrl: string, store: boolean): void {
        this.httpService.changeApiBaseUrl(apiBaseUrl, store);

        this.router.navigate(['']);
    }
}
