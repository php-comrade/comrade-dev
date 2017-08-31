import {Injectable} from "@angular/core";
import {CanActivate} from "@angular/router";
import {HttpService} from "./http.service";

import 'rxjs/add/operator/map';
import 'rxjs/add/operator/do';

@Injectable()
export class ApiGuard implements CanActivate {
    constructor(private httpService: HttpService) {}

    canActivate() {
        return this.httpService.getApiBaseUrlObservable().map(apiBaseUrl => !!apiBaseUrl);
    }
}
